<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Puzzle;
use App\Models\PuzzleAttempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserMissionProgress;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Locks in the progression, reward and content-integrity rules with self-contained fixtures.
 * Existing topics are hidden (inside the rolled-back test transaction) so only fixtures count.
 */
class ProgressionIntegrityTest extends TestCase
{
    private User $student;
    private User $lecturer;

    protected function setUp(): void
    {
        parent::setUp();

        Topic::query()->update(['status' => 'draft']);

        $this->student = User::create([
            'name' => 'Progression Student',
            'email' => 'prog_student_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
        ]);

        $this->lecturer = User::create([
            'name' => 'Progression Lecturer',
            'email' => 'prog_lecturer_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'),
            'role' => 'lecturer',
        ]);
    }

    private function topic(int $order, string $status = 'published'): Topic
    {
        return Topic::create([
            'title' => "Fixture Topic {$order}",
            'content' => 'Fixture content',
            'topic_order' => 1000 + $order,
            'status' => $status,
            'created_by' => $this->lecturer->user_id,
        ]);
    }

    private function mission(Topic $topic, int $order, array $extra = []): Mission
    {
        return Mission::create(array_merge([
            'topic_id' => $topic->topic_id,
            'title' => "Fixture Mission {$order}",
            'difficulty' => 'beginner',
            'mission_order' => $order,
            'pass_mark' => 70,
            'xp_reward' => 150,
            'status' => 'published',
        ], $extra));
    }

    private function identificationPuzzle(Mission $mission, int $order = 1, array $extra = []): Puzzle
    {
        return Puzzle::create(array_merge([
            'mission_id' => $mission->mission_id,
            'topic_id' => $mission->topic_id,
            'puzzle_type' => 'identification',
            'question_text' => "Fixture puzzle {$order}",
            'instruction' => 'Pick the right option',
            'activity_data' => ['options' => [['id' => 'opt_1', 'label' => 'Right'], ['id' => 'opt_2', 'label' => 'Wrong']]],
            'correct_answer' => ['correct_option' => 'opt_1'],
            'points' => 100,
            'activity_order' => $order,
            'is_required' => true,
            'status' => 'published',
        ], $extra));
    }

    private function quizWithQuestion(Topic $topic): Quiz
    {
        $quiz = Quiz::create(['topic_id' => $topic->topic_id, 'title' => 'Fixture Quiz', 'passing_mark' => 50]);
        Question::create([
            'quiz_id' => $quiz->quiz_id,
            'question_text' => 'Fixture question?',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        return $quiz;
    }

    private function completeTopic(Topic $topic): void
    {
        Progress::updateOrCreate(
            ['user_id' => $this->student->user_id, 'topic_id' => $topic->topic_id],
            ['learning_status' => 'Completed', 'quiz_status' => 'Passed', 'progress_percentage' => 100]
        );
    }

    // ── Topic unlocking ──────────────────────────────────────────────────

    public function test_first_visible_topic_is_open_and_next_requires_completion()
    {
        $t1 = $this->topic(1);
        $t2 = $this->topic(2);

        $this->assertTrue($t1->isUnlockedFor($this->student->user_id));
        $this->assertFalse($t2->isUnlockedFor($this->student->user_id));
        $this->actingAs($this->student)->get(route('student.topic', $t2))->assertRedirect(route('student.learning'));

        $this->completeTopic($t1);
        $this->assertTrue($t2->fresh()->isUnlockedFor($this->student->user_id));
        $this->actingAs($this->student)->get(route('student.topic', $t2))->assertOk();
    }

    public function test_draft_topic_is_skipped_and_cannot_be_opened_by_url()
    {
        $t1 = $this->topic(1);
        $draft = $this->topic(2, 'draft');
        $t3 = $this->topic(3);

        $this->completeTopic($t1);

        // The draft topic never blocks the sequence...
        $this->assertTrue($t3->isUnlockedFor($this->student->user_id));
        // ...and is never reachable directly
        $this->assertFalse($draft->isUnlockedFor($this->student->user_id));
        $this->actingAs($this->student)->get(route('student.topic', $draft))->assertRedirect(route('student.learning'));
    }

    public function test_quiz_of_locked_topic_cannot_be_opened_or_submitted()
    {
        $this->topic(1);
        $locked = $this->topic(2);
        $quiz = $this->quizWithQuestion($locked);
        $questionId = $quiz->questions()->first()->question_id;

        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))->assertRedirect(route('student.quiz'));
        $this->actingAs($this->student)->post(route('student.quiz.submit'), [
            'quiz_id' => $quiz->quiz_id,
            'answers' => [$questionId => 'A'],
        ])->assertRedirect(route('student.learning'));

        $this->assertSame(0, Result::where('user_id', $this->student->user_id)->count());
    }

    public function test_journey_and_progress_pages_report_published_mission_counts()
    {
        $topic = $this->topic(1);
        $this->identificationPuzzle($this->mission($topic, 1));
        $this->identificationPuzzle($this->mission($topic, 2));
        $this->identificationPuzzle($this->mission($topic, 3, ['status' => 'draft']));
        $this->mission($topic, 4); // published but empty: not playable, not counted

        foreach (['student.learning' => 'topics', 'student.progress' => 'topicProgress'] as $route => $key) {
            $rows = $this->actingAs($this->student)->get(route($route))->assertOk()->viewData($key);
            $row = $rows->firstWhere('topic_id', $topic->topic_id);
            $this->assertSame(2, (int) $row->missions_count, "{$route} should count only playable missions");
        }
    }

    // ── Mission unlocking ────────────────────────────────────────────────

    public function test_later_mission_unlocks_only_after_previous_is_completed()
    {
        $topic = $this->topic(1);
        $m1 = $this->mission($topic, 1);
        $this->identificationPuzzle($m1);
        $m2 = $this->mission($topic, 2);
        $p2 = $this->identificationPuzzle($m2);

        $this->assertTrue($m1->isUnlockedFor($this->student->user_id));
        $this->assertFalse($m2->isUnlockedFor($this->student->user_id));
        $this->actingAs($this->student)->getJson("/puzzles/{$p2->puzzle_id}/data")->assertStatus(403);

        UserMissionProgress::create([
            'user_id' => $this->student->user_id, 'mission_id' => $m1->mission_id,
            'is_unlocked' => true, 'is_completed' => true,
        ]);

        $this->assertTrue($m2->isUnlockedFor($this->student->user_id));
        $this->actingAs($this->student)->getJson("/puzzles/{$p2->puzzle_id}/data")->assertOk();
    }

    public function test_standalone_puzzle_without_mission_cannot_be_played()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $standalone = $this->identificationPuzzle($mission);
        $standalone->update(['mission_id' => null]);

        $this->actingAs($this->student)->get("/puzzles/{$standalone->puzzle_id}")->assertStatus(403);
        $this->actingAs($this->student)->getJson("/puzzles/{$standalone->puzzle_id}/data")->assertStatus(403);
        $this->actingAs($this->student)->postJson("/puzzles/{$standalone->puzzle_id}/submit", ['selected_option' => 'opt_1'])->assertStatus(403);

        $this->assertSame(0, PuzzleAttempt::where('puzzle_id', $standalone->puzzle_id)->count());
    }

    public function test_empty_mission_never_blocks_or_counts()
    {
        $topic = $this->topic(1);
        $this->mission($topic, 1); // published but has no activities
        $real = $this->mission($topic, 2);
        $puzzle = $this->identificationPuzzle($real);

        // The empty mission is skipped: mission 2 is open and the only one counted/shown
        $this->assertTrue($real->isUnlockedFor($this->student->user_id));
        $this->actingAs($this->student)->get(route('student.topic', $topic))->assertOk()
            ->assertViewHas('missions', fn ($missions) => $missions->pluck('mission_id')->all() === [$real->mission_id]);

        // Finishing the only playable mission completes a quizless topic
        $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", ['selected_option' => 'opt_1'])->assertOk();
        $this->assertSame('Completed', Progress::where('user_id', $this->student->user_id)->where('topic_id', $topic->topic_id)->value('learning_status'));
    }

    public function test_mission_added_after_completion_is_reachable()
    {
        $topic = $this->topic(1);
        $m1 = $this->mission($topic, 1);
        UserMissionProgress::create([
            'user_id' => $this->student->user_id, 'mission_id' => $m1->mission_id,
            'is_unlocked' => true, 'is_completed' => true,
        ]);

        // Lecturer adds a new mission after the student already finished Mission 1
        $late = $this->mission($topic, 2);

        $this->assertTrue($late->isUnlockedFor($this->student->user_id));
    }

    public function test_mission_without_required_flags_can_still_be_completed()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission, 1, ['is_required' => false]);

        $response = $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", [
            'selected_option' => 'opt_1',
        ]);

        $response->assertOk()->assertJson(['mission_completed' => true]);
    }

    public function test_topic_without_quiz_completes_when_all_missions_are_done()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission);

        $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", [
            'selected_option' => 'opt_1',
        ])->assertOk();

        $progress = Progress::where('user_id', $this->student->user_id)->where('topic_id', $topic->topic_id)->first();
        $this->assertSame('Completed', $progress->learning_status);
        $this->assertSame(100, (int) $progress->progress_percentage);
    }

    public function test_topic_with_quiz_still_requires_the_quiz_after_missions()
    {
        $topic = $this->topic(1);
        $this->quizWithQuestion($topic);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission);

        $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", [
            'selected_option' => 'opt_1',
        ])->assertOk();

        $progress = Progress::where('user_id', $this->student->user_id)->where('topic_id', $topic->topic_id)->first();
        $this->assertSame('In Progress', $progress->learning_status);
        $this->assertSame(80, // 10% opened + 70% for all missions; the quiz is the last 20%
            (int) $progress->progress_percentage);
    }

    // ── XP and rank consistency ──────────────────────────────────────────

    public function test_quiz_xp_is_counted_once_in_totals()
    {
        $topic = $this->topic(1);
        $quiz = $this->quizWithQuestion($topic);
        $questionId = $quiz->questions()->first()->question_id;

        $this->actingAs($this->student)->post(route('student.quiz.submit'), [
            'quiz_id' => $quiz->quiz_id,
            'answers' => [$questionId => 'A'],
        ])->assertRedirect();

        $this->assertSame(100, (int) Result::where('user_id', $this->student->user_id)->sum('points_earned'));
        // Quiz XP lives on the result only; topic progress XP is reserved for puzzle/mission XP
        $this->assertSame(0, (int) Progress::where('user_id', $this->student->user_id)->sum('total_xp'));

        $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()->assertViewHas('totalPoints', 100);
    }

    public function test_puzzle_response_reports_global_rank_including_quiz_xp()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1, ['xp_reward' => 100]);
        $puzzle = $this->identificationPuzzle($mission);

        // 400 quiz XP earned elsewhere
        $otherQuiz = Quiz::create(['topic_id' => $topic->topic_id, 'title' => 'Other', 'passing_mark' => 50]);
        Result::create([
            'user_id' => $this->student->user_id, 'quiz_id' => $otherQuiz->quiz_id,
            'score' => 1, 'total_questions' => 1, 'percentage' => 100, 'status' => 'Pass',
            'points_earned' => 400, 'date_taken' => now(),
        ]);

        // +100 puzzle XP +100 mission reward = 600 total → level 2
        $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", [
            'selected_option' => 'opt_1',
        ])->assertOk()->assertJson([
            'total_xp' => 600,
            'clearance_level' => 2,
            'clearance_rank' => 'Clearance Level 2: Packet Analyst',
        ]);
    }

    public function test_repeating_a_solved_puzzle_awards_no_extra_xp()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission);

        $first = $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", ['selected_option' => 'opt_1']);
        $second = $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", ['selected_option' => 'opt_1']);

        $this->assertSame(100, $first->json('earned_xp'));
        $this->assertSame(0, $second->json('earned_xp'));
        $this->assertSame(2, PuzzleAttempt::where('puzzle_id', $puzzle->puzzle_id)->count());
    }

    // ── Content integrity ────────────────────────────────────────────────

    public function test_badge_cannot_be_recorded_twice_for_same_student()
    {
        $achievement = Achievement::first() ?? Achievement::create([
            'badge_name' => 'Fixture Badge', 'description' => 'x',
            'requirement_type' => 'xp_earned', 'requirement_value' => 1,
        ]);

        UserAchievement::create([
            'user_id' => $this->student->user_id, 'achievement_id' => $achievement->achievement_id, 'date_earned' => now(),
        ]);

        $this->expectException(QueryException::class);
        UserAchievement::create([
            'user_id' => $this->student->user_id, 'achievement_id' => $achievement->achievement_id, 'date_earned' => now(),
        ]);
    }

    public function test_content_with_student_history_cannot_be_deleted()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission);

        $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", ['selected_option' => 'opt_2'])->assertOk();

        $this->actingAs($this->lecturer)->delete(route('lecturer.missions.puzzles.destroy', $puzzle))->assertSessionHas('error');
        $this->actingAs($this->lecturer)->delete(route('lecturer.missions.destroy', $mission))->assertSessionHas('error');
        $this->actingAs($this->lecturer)->delete(route('lecturer.topics.destroy', $topic))->assertSessionHas('error');

        $this->assertNotNull(Puzzle::find($puzzle->puzzle_id));
        $this->assertNotNull(Mission::find($mission->mission_id));
        $this->assertNotNull(Topic::find($topic->topic_id));
        $this->assertSame(1, PuzzleAttempt::where('puzzle_id', $puzzle->puzzle_id)->count());
    }

    public function test_content_without_student_history_can_be_deleted()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission);

        $this->actingAs($this->lecturer)->delete(route('lecturer.missions.puzzles.destroy', $puzzle))->assertSessionHas('success');
        $this->actingAs($this->lecturer)->delete(route('lecturer.missions.destroy', $mission))->assertSessionHas('success');
        $this->actingAs($this->lecturer)->delete(route('lecturer.topics.destroy', $topic))->assertSessionHas('success');

        $this->assertNull(Topic::find($topic->topic_id));
    }

    public function test_mission_with_attempts_cannot_move_to_another_topic()
    {
        $topic = $this->topic(1);
        $other = $this->topic(2);
        $mission = $this->mission($topic, 1);
        $puzzle = $this->identificationPuzzle($mission);
        $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", ['selected_option' => 'opt_2'])->assertOk();

        $this->actingAs($this->lecturer)->put(route('lecturer.missions.update', $mission), [
            'topic_id' => $other->topic_id,
            'title' => $mission->title,
            'difficulty' => 'beginner',
            'mission_order' => 1,
            'pass_mark' => 70,
            'xp_reward' => 150,
            'status' => 'published',
        ])->assertSessionHasErrors('topic_id');

        $this->assertSame($topic->topic_id, $mission->fresh()->topic_id);
    }

    public function test_duplicate_positions_are_rejected()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);
        $this->identificationPuzzle($mission, 1);

        $this->actingAs($this->lecturer)->post(route('lecturer.topics.store'), [
            'title' => 'Duplicate stage', 'content' => 'x', 'topic_order' => $topic->topic_order,
        ])->assertSessionHasErrors('topic_order');

        $this->actingAs($this->lecturer)->post(route('lecturer.missions.store'), [
            'topic_id' => $topic->topic_id, 'title' => 'Duplicate mission', 'difficulty' => 'beginner',
            'mission_order' => 1, 'pass_mark' => 70, 'xp_reward' => 150, 'status' => 'draft',
        ])->assertSessionHasErrors('mission_order');

        $this->actingAs($this->lecturer)->post(route('lecturer.missions.puzzles.store', $mission), [
            'puzzle_type' => 'identification', 'question_text' => 'Dup', 'instruction' => 'Dup',
            'points' => 100, 'activity_order' => 1, 'difficulty' => 'beginner', 'status' => 'draft',
            'options' => ['A', 'B'], 'correct_option_index' => 0,
        ])->assertSessionHasErrors('activity_order');
    }

    public function test_puzzle_form_keeps_correct_answer_aligned_when_rows_are_blank()
    {
        $topic = $this->topic(1);
        $mission = $this->mission($topic, 1);

        // Option at form index 1 is blank; the lecturer marked form index 2 ("Router") as correct
        $this->actingAs($this->lecturer)->post(route('lecturer.missions.puzzles.store', $mission), [
            'puzzle_type' => 'identification', 'question_text' => 'Which device routes?', 'instruction' => 'Pick one',
            'points' => 100, 'activity_order' => 5, 'difficulty' => 'beginner', 'status' => 'published',
            'options' => ['Hub', '', 'Router'], 'correct_option_index' => 2,
        ])->assertSessionHasNoErrors();

        $puzzle = Puzzle::where('mission_id', $mission->mission_id)->where('activity_order', 5)->firstOrFail();
        $correctId = $puzzle->correct_answer['correct_option'];
        $label = collect($puzzle->activity_data['options'])->firstWhere('id', $correctId)['label'];

        $this->assertSame('Router', $label);
    }
}
