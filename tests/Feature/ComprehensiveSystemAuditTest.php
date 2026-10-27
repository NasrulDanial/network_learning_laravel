<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Topic;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Result;
use App\Models\Progress;
use App\Models\PuzzleAttempt;
use App\Models\UserPuzzleProgress;
use App\Models\UserMissionProgress;

class ComprehensiveSystemAuditTest extends TestCase
{
    private User $student;
    private User $lecturer;
    private Topic $topic;
    private Mission $mission;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student  = User::where('role', 'student')->firstOrFail();
        $this->lecturer = User::where('role', 'lecturer')->firstOrFail();
        $this->topic    = Topic::firstOrFail();
        $this->mission  = Mission::where('status', 'published')->firstOrFail();
        $this->quiz     = Quiz::firstOrFail();
    }

    // =========================================================================
    // SECTION 1: STUDENT VIEW ACCESSIBILITY & BUTTON TARGETS
    // =========================================================================

    public function test_student_can_access_dashboard()
    {
        $response = $this->actingAs($this->student)->get('/student/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Welcome back', false);
        $response->assertSee('Operative Telemetry', false);
    }

    public function test_student_can_access_learning_journey()
    {
        $response = $this->actingAs($this->student)->get('/student/learning');
        $response->assertStatus(200);
        $response->assertSee('Networking Adventure', false);
        $response->assertSee($this->topic->title);
    }

    public function test_student_can_access_topic_missions_board()
    {
        $response = $this->actingAs($this->student)->get("/student/topic/{$this->topic->topic_id}");
        $response->assertStatus(200);
        $response->assertSee('Learning Missions', false);
        $response->assertSee($this->topic->title);
    }

    public function test_student_can_access_progress_telemetry()
    {
        $response = $this->actingAs($this->student)->get('/student/progress');
        $response->assertStatus(200);
        $response->assertSee('Learning Progress', false);
        $response->assertSee('TOTAL XP', false);
        $response->assertSee('MISSIONS CLEARED', false);
    }

    public function test_student_can_access_quiz_hub()
    {
        $response = $this->actingAs($this->student)->get('/student/quiz');
        $response->assertStatus(200);
        $response->assertSee('Knowledge Challenges', false);
    }

    public function test_student_can_take_quiz()
    {
        $response = $this->actingAs($this->student)->get("/student/quiz/{$this->quiz->quiz_id}");
        $response->assertStatus(200);
        $response->assertSee($this->quiz->title);
    }

    public function test_student_can_view_quiz_result_if_exists()
    {
        $result = Result::where('user_id', $this->student->user_id)->first();
        if ($result) {
            $response = $this->actingAs($this->student)->get("/student/result/{$result->result_id}");
            $response->assertStatus(200);
            $response->assertSee('Correct Answers', false);
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // SECTION 2: LECTURER VIEW ACCESSIBILITY & BUTTON TARGETS
    // =========================================================================

    public function test_lecturer_can_access_dashboard()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Instructor Mission Control', false);
        $response->assertSee('Published Topics', false);
        $response->assertSee('Published Missions', false);
    }

    public function test_lecturer_can_access_missions_index()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/missions');
        $response->assertStatus(200);
        $response->assertSee('Learning Missions', false);
        $response->assertSee('Puzzle Activities', false);
    }

    public function test_lecturer_can_access_missions_create()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/missions/create');
        $response->assertStatus(200);
        $response->assertSee('Create Learning Mission', false);
    }

    public function test_lecturer_can_access_mission_show_page()
    {
        $response = $this->actingAs($this->lecturer)->get("/lecturer/missions/{$this->mission->mission_id}");
        $response->assertStatus(200);
        $response->assertSee($this->mission->title);
        $response->assertSee('Add Puzzle Activity', false);
    }

    public function test_lecturer_can_access_mission_edit_page()
    {
        $response = $this->actingAs($this->lecturer)->get("/lecturer/missions/{$this->mission->mission_id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit Mission', false);
    }

    public function test_lecturer_can_access_quizzes_management()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/quizzes');
        $response->assertStatus(200);
        $response->assertSee('Manage Quiz Challenges & Missions', false);
    }

    public function test_lecturer_can_access_quiz_create()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/quizzes/create');
        $response->assertStatus(200);
    }

    public function test_lecturer_can_access_quiz_edit()
    {
        $response = $this->actingAs($this->lecturer)->get("/lecturer/quizzes/{$this->quiz->quiz_id}/edit");
        $response->assertStatus(200);
    }

    public function test_lecturer_can_access_quiz_questions()
    {
        $response = $this->actingAs($this->lecturer)->get("/lecturer/quizzes/{$this->quiz->quiz_id}/questions");
        $response->assertStatus(200);
    }

    public function test_lecturer_can_access_topics_index()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/topics');
        $response->assertStatus(200);
        $response->assertSee('Manage Networking Topics', false);
        $response->assertSee('Missions', false);
    }

    public function test_lecturer_can_access_topic_create()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/topics/create');
        $response->assertStatus(200);
    }

    public function test_lecturer_can_access_topic_edit()
    {
        $response = $this->actingAs($this->lecturer)->get("/lecturer/topics/{$this->topic->topic_id}/edit");
        $response->assertStatus(200);
    }

    public function test_lecturer_can_access_student_performance()
    {
        $response = $this->actingAs($this->lecturer)->get('/lecturer/student-performance');
        $response->assertStatus(200);
        $response->assertSee('Student Performance Telemetry', false);
    }

    // =========================================================================
    // SECTION 3: ROLE SECURITY & AUTHORIZATION BOUNDARIES
    // =========================================================================

    public function test_student_cannot_access_lecturer_dashboard()
    {
        $response = $this->actingAs($this->student)->get('/lecturer/dashboard');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_lecturer_missions()
    {
        $response = $this->actingAs($this->student)->get('/lecturer/missions');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_lecturer_quizzes()
    {
        $response = $this->actingAs($this->student)->get('/lecturer/quizzes');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_lecturer_topics()
    {
        $response = $this->actingAs($this->student)->get('/lecturer/topics');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_student_performance_telemetry()
    {
        $response = $this->actingAs($this->student)->get('/lecturer/student-performance');
        $response->assertStatus(403);
    }

    public function test_lecturer_cannot_play_puzzle_as_student()
    {
        $puzzle = Puzzle::where('status', 'published')->first();
        if ($puzzle) {
            $response = $this->actingAs($this->lecturer)->get("/puzzles/{$puzzle->puzzle_id}");
            $response->assertStatus(403);
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // SECTION 4: ALL 5 (+1) PUZZLE ARCHETYPES EXECUTION & EVALUATION
    // =========================================================================

    public function test_all_six_archetypes_have_valid_preview()
    {
        $types = ['identification', 'matching', 'sequencing', 'classification', 'drag_and_drop', 'scenario'];

        foreach ($types as $type) {
            $p = Puzzle::where('puzzle_type', $type)->where('status', 'published')->first();
            if ($p) {
                $response = $this->actingAs($this->lecturer)->get("/lecturer/puzzles/{$p->puzzle_id}/preview");
                $response->assertStatus(200);
                $response->assertSee('LECTURER TEST PREVIEW', false);
            }
        }
    }

    public function test_puzzle_safe_data_strips_answer_key_for_all_types()
    {
        $puzzles = Puzzle::where('status', 'published')->get();

        foreach ($puzzles as $p) {
            $response = $this->actingAs($this->student)->getJson("/puzzles/{$p->puzzle_id}/data");
            $response->assertStatus(200);
            $data = $response->json();

            // Truth table answer keys must NEVER be present
            $this->assertArrayNotHasKey('correct_answer', $data);
            $this->assertArrayNotHasKey('correct_option', $data);
            $this->assertArrayNotHasKey('pairs', $data);
            $this->assertArrayNotHasKey('order', $data);
            $this->assertArrayNotHasKey('mapping', $data);
            $this->assertArrayNotHasKey('correct_choice', $data);
        }
    }

    public function test_lecturer_preview_evaluates_answers_without_logging_attempts_or_awarding_xp()
    {
        $puzzle = Puzzle::where('status', 'published')->first();
        if (!$puzzle) return;

        $initialAttempts = PuzzleAttempt::where('puzzle_id', $puzzle->puzzle_id)->count();

        $response = $this->actingAs($this->lecturer)->postJson(
            route('lecturer.missions.puzzles.preview.submit', $puzzle->puzzle_id),
            ['selected_option' => 'opt_1']
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['earned_xp']);
        $this->assertStringContainsString('Lecturer Sandbox Preview', $data['clearance_status']);

        // Assert zero database mutations in puzzle_attempts
        $this->assertEquals($initialAttempts, PuzzleAttempt::where('puzzle_id', $puzzle->puzzle_id)->count());
    }

    public function test_tampered_is_correct_rejected_with_422()
    {
        $puzzle = Puzzle::where('status', 'published')->first();
        if (!$puzzle) return;

        $response = $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", [
            'is_correct' => true,
            'score'      => 9999,
        ]);

        $response->assertStatus(422);
    }

    public function test_quiz_attempt_idempotency_prevents_repeat_xp_farming()
    {
        // Create an isolated student directly (no factory — avoids email_verified_at
        // which is not in the users table schema).
        $isolatedStudent = User::create([
            'name'     => 'Idempotency Test Student',
            'email'    => 'idempotency_test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role'     => 'student',
        ]);

        // Load the first quiz and its actual questions so we can build a
        // 100 % correct answer payload — guaranteeing a Pass result.
        $quiz      = Quiz::with(['questions', 'topic'])->firstOrFail();
        $questions = $quiz->questions;

        // Build a fully-correct answers map keyed by real question_id.
        $correctAnswers = [];
        foreach ($questions as $question) {
            $correctAnswers[$question->question_id] = $question->correct_answer;
        }

        // ── Round 1: first ever Pass ─────────────────────────────────────────
        $response = $this->actingAs($isolatedStudent)->post('/student/quiz/submit', [
            'quiz_id' => $quiz->quiz_id,
            'answers' => $correctAnswers,
        ]);

        $response->assertStatus(302);   // redirect on success

        $firstResult = Result::where('user_id', $isolatedStudent->user_id)
            ->where('quiz_id', $quiz->quiz_id)
            ->latest('result_id')
            ->firstOrFail();

        $this->assertEquals('Pass', $firstResult->status,
            'Round-1 submission with all correct answers must produce a Pass.');
        $this->assertGreaterThan(0, $firstResult->points_earned,
            'First ever Pass should award XP (points_earned > 0).');

        // ── Round 2: repeat Pass — XP must be 0 (idempotent guard) ──────────
        $response2 = $this->actingAs($isolatedStudent)->post('/student/quiz/submit', [
            'quiz_id' => $quiz->quiz_id,
            'answers' => $correctAnswers,
        ]);

        $response2->assertStatus(302);

        $secondResult = Result::where('user_id', $isolatedStudent->user_id)
            ->where('quiz_id', $quiz->quiz_id)
            ->latest('result_id')
            ->firstOrFail();

        $this->assertEquals('Pass', $secondResult->status,
            'Round-2 submission must still be a Pass.');
        $this->assertEquals(0, $secondResult->points_earned,
            'Repeat Pass must award 0 XP to prevent farming.');

        // Clean up the isolated user record so the live DB stays tidy.
        Result::where('user_id', $isolatedStudent->user_id)->delete();
        $isolatedStudent->delete();
    }
}
