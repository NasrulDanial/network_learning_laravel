<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Services\PuzzleEvaluatorService;
use Database\Seeders\CurriculumSeeder;
use Tests\TestCase;

class CurriculumSeederTest extends TestCase
{
    private array $curriculum;

    protected function setUp(): void
    {
        parent::setUp();
        $this->curriculum = require base_path('database/seeders/data/netlearn_curriculum.php');
        $this->seed(CurriculumSeeder::class);
    }

    public function test_curriculum_has_five_topics_with_ten_questions_and_three_missions_each()
    {
        $this->assertCount(5, $this->curriculum);

        foreach ($this->curriculum as $spec) {
            $topic = Topic::where('title', $spec['title'])->firstOrFail();
            $quiz = Quiz::where('topic_id', $topic->topic_id)->orderBy('quiz_id')->firstOrFail();

            $this->assertSame(10, count($spec['quiz']['questions']), $spec['title']);
            $this->assertGreaterThanOrEqual(10, Question::where('quiz_id', $quiz->quiz_id)->count());

            $this->assertCount(3, $spec['missions'], $spec['title']);
            foreach ($spec['missions'] as $missionSpec) {
                $mission = Mission::where('topic_id', $topic->topic_id)->where('title', $missionSpec['title'])->firstOrFail();
                $this->assertSame(3, Puzzle::where('mission_id', $mission->mission_id)->where('status', 'published')->count(), $missionSpec['title']);
            }
        }
    }

    public function test_every_seeded_puzzle_is_solvable_and_rejects_a_wrong_answer()
    {
        $evaluator = app(PuzzleEvaluatorService::class);
        $titles = collect($this->curriculum)->flatMap(fn ($t) => collect($t['missions'])->flatMap(fn ($m) => collect($m['puzzles'])->pluck('title')));

        $this->assertCount(45, $titles);
        $this->assertSame(45, $titles->unique()->count(), 'Puzzle titles must be unique (they are the seed identity)');

        foreach (Puzzle::whereIn('question_text', $titles)->get() as $puzzle) {
            [$right, $wrong] = $this->submissions($puzzle);

            $good = $evaluator->evaluate($puzzle, $right);
            $this->assertTrue($good['is_correct'], "Correct answer rejected for \"{$puzzle->question_text}\"");
            $this->assertSame((int) $puzzle->points, $good['score']);

            $bad = $evaluator->evaluate($puzzle, $wrong);
            $this->assertFalse($bad['is_correct'], "Wrong answer accepted for \"{$puzzle->question_text}\"");

            // Students never receive the answer key
            $safe = $puzzle->getSafeActivityData();
            $this->assertArrayNotHasKey('correct_answer', $safe);
            $this->assertStringNotContainsString('correct_option', json_encode($safe));
        }
    }

    public function test_quiz_answer_keys_are_valid_and_spread_across_options()
    {
        foreach ($this->curriculum as $spec) {
            $letters = array_column($spec['quiz']['questions'], 5);
            $this->assertEmpty(array_diff($letters, ['A', 'B', 'C', 'D']), $spec['title']);

            // No answer letter should dominate a quiz (guessing one letter must not pass)
            $mostCommon = max(array_count_values($letters));
            $this->assertLessThanOrEqual(4, $mostCommon, "{$spec['title']}: one letter is correct {$mostCommon}/10 times");

            foreach ($spec['quiz']['questions'] as $q) {
                $options = array_slice($q, 1, 4);
                $this->assertSame(4, count(array_unique($options)), "Duplicate options in \"{$q[0]}\"");
            }
        }
    }

    public function test_seeding_again_creates_nothing_and_keeps_lecturer_edits()
    {
        $topic = Topic::where('title', $this->curriculum[0]['title'])->firstOrFail();
        $topic->update(['description' => 'Edited by a lecturer']);
        $puzzle = Puzzle::where('topic_id', $topic->topic_id)->firstOrFail();
        $puzzle->update(['hint' => 'Lecturer hint']);

        $counts = fn () => [Topic::count(), Quiz::count(), Question::count(), Mission::count(), Puzzle::count()];
        $before = $counts();

        $this->seed(CurriculumSeeder::class);

        $this->assertSame($before, $counts());
        $this->assertSame('Edited by a lecturer', $topic->fresh()->description);
        $this->assertSame('Lecturer hint', $puzzle->fresh()->hint);
    }

    public function test_positions_stay_unique_per_topic_and_mission()
    {
        $this->assertSame(Topic::count(), Topic::distinct()->count('topic_order'));

        foreach (Topic::all() as $topic) {
            $orders = Mission::where('topic_id', $topic->topic_id)->pluck('mission_order');
            $this->assertSame($orders->count(), $orders->unique()->count(), "Duplicate mission order in {$topic->title}");
        }

        foreach (Mission::all() as $mission) {
            $orders = Puzzle::where('mission_id', $mission->mission_id)->pluck('activity_order');
            $this->assertSame($orders->count(), $orders->unique()->count(), "Duplicate activity order in {$mission->title}");
        }
    }

    public function test_seeded_content_is_playable_end_to_end_for_a_new_student()
    {
        $student = \App\Models\User::create([
            'name' => 'Seed Check Student',
            'email' => 'seedcheck_' . uniqid() . '@netlearn.test',
            'password' => bcrypt('password123'),
            'role' => 'student',
        ]);

        $first = Topic::visibleToStudents()->orderBy('topic_order')->firstOrFail();
        $this->actingAs($student)->get(route('student.topic', $first))->assertOk()->assertSee($first->title);

        $mission = Mission::playable()->where('topic_id', $first->topic_id)->orderBy('mission_order')->firstOrFail();
        foreach (Puzzle::where('mission_id', $mission->mission_id)->get() as $puzzle) {
            [$right] = $this->submissions($puzzle);
            $this->actingAs($student)->get("/puzzles/{$puzzle->puzzle_id}")->assertOk();
            $this->actingAs($student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", $right)->assertOk()->assertJson(['status' => 'completed']);
        }

        $this->assertTrue(
            \App\Models\UserMissionProgress::where('user_id', $student->user_id)->where('mission_id', $mission->mission_id)->value('is_completed')
        );
    }

    /** Build a correct and a wrong student submission from the stored answer key. */
    private function submissions(Puzzle $puzzle): array
    {
        $key = $puzzle->correct_answer;
        $data = $puzzle->activity_data;

        switch ($puzzle->puzzle_type) {
            case 'identification':
                $other = collect($data['options'])->pluck('id')->first(fn ($id) => $id !== $key['correct_option']);
                return [['selected_option' => $key['correct_option']], ['selected_option' => $other]];
            case 'scenario':
                $other = collect($data['choices'])->pluck('id')->first(fn ($id) => $id !== $key['correct_choice']);
                return [['choice_id' => $key['correct_choice']], ['choice_id' => $other]];
            case 'matching':
                $wrong = $key['pairs'];
                $l = array_keys($wrong);
                [$wrong[$l[0]], $wrong[$l[1]]] = [$wrong[$l[1]], $wrong[$l[0]]];
                return [['pairs' => $key['pairs']], ['pairs' => $wrong]];
            case 'sequencing':
                return [['ordered_ids' => $key['order']], ['ordered_ids' => array_reverse($key['order'])]];
            case 'classification':
                $wrong = $key['mapping'];
                $cats = collect($data['categories'])->pluck('id');
                $first = array_key_first($wrong);
                $wrong[$first] = $cats->first(fn ($c) => $c !== $wrong[$first]);
                return [['placements' => $key['mapping']], ['placements' => $wrong]];
            case 'drag_and_drop':
                $wrong = $key['placements'];
                $d = array_keys($wrong);
                [$wrong[$d[0]], $wrong[$d[1]]] = [$wrong[$d[1]], $wrong[$d[0]]];
                return [['placements' => $key['placements']], ['placements' => $wrong]];
        }

        $this->fail("Unhandled puzzle type {$puzzle->puzzle_type}");
    }
}
