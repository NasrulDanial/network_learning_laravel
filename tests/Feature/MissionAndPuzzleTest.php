<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Topic;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Services\PuzzleEvaluatorService;
use App\Services\MissionProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MissionAndPuzzleTest extends TestCase
{
    // NOTE: We do NOT use RefreshDatabase — we preserve existing user data
    // per the non-destructive constraint. Tests use real DB with sandbox users.

    private User $student;
    private User $lecturer;
    private Mission $mission;
    private Puzzle $identPuzzle;
    private Puzzle $matchPuzzle;
    private Puzzle $seqPuzzle;
    private Puzzle $classPuzzle;
    private Puzzle $dragPuzzle;
    private Puzzle $scenarioPuzzle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Use existing roles from DB
        $this->student  = User::where('role', 'student')->firstOrFail();
        $this->lecturer = User::where('role', 'lecturer')->firstOrFail();
        $this->mission  = Mission::where('status', 'published')->with('puzzles')->firstOrFail();

        // Fetch one of each puzzle type from the seeded data
        $this->identPuzzle    = Puzzle::where('puzzle_type', 'identification')->where('status', 'published')->firstOrFail();
        $this->matchPuzzle    = Puzzle::where('puzzle_type', 'matching')->where('status', 'published')->firstOrFail();
        $this->seqPuzzle      = Puzzle::where('puzzle_type', 'sequencing')->where('status', 'published')->firstOrFail();
        $this->classPuzzle    = Puzzle::where('puzzle_type', 'classification')->where('status', 'published')->firstOrFail();
        $this->dragPuzzle     = Puzzle::where('puzzle_type', 'drag_and_drop')->where('status', 'published')->firstOrFail();
        $this->scenarioPuzzle = Puzzle::where('puzzle_type', 'scenario')->where('status', 'published')->firstOrFail();
    }

    // ═══════════════════════════════════════════════════════════════
    // ROLE SECURITY TESTS
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function public_registration_cannot_create_lecturer_account()
    {
        $response = $this->post('/register', [
            'name'                  => 'Hacker McHack',
            'email'                 => 'hacker' . time() . '@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'lecturer', // Attempt privilege escalation
        ]);

        $response->assertRedirect();

        // The newly created user should always be 'student' regardless of form input
        $newUser = User::where('email', 'like', 'hacker%')->latest()->first();
        if ($newUser) {
            $this->assertEquals('student', $newUser->role, 'Registration must never create a lecturer via public form');
            $newUser->forceDelete();
        }
    }

    /** @test */
    public function student_cannot_access_lecturer_missions_dashboard()
    {
        $response = $this->actingAs($this->student)->get(route('lecturer.missions.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function lecturer_cannot_submit_puzzle_attempts_to_earn_xp()
    {
        // Puzzle submit route is student-only middleware
        $response = $this->actingAs($this->lecturer)->postJson(
            route('puzzles.submit', $this->identPuzzle->puzzle_id),
            ['selected_option' => 'opt_1']
        );
        $response->assertStatus(403); // Role guard aborts with 403
    }

    /** @test */
    public function student_cannot_access_puzzle_authoring_routes()
    {
        $response = $this->actingAs($this->student)->get(
            route('lecturer.missions.index')
        );
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // SAFE JSON — answer keys must never be exposed to students
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function puzzle_data_endpoint_does_not_expose_correct_answer()
    {
        $response = $this->actingAs($this->student)->getJson(
            route('puzzles.data', $this->identPuzzle->puzzle_id)
        );

        $response->assertStatus(200);
        $data = $response->json();

        // Must NOT contain the answer key
        $this->assertArrayNotHasKey('correct_answer', $data);
        $this->assertArrayNotHasKey('correct_option', $data);

        // Must contain the safe data
        $this->assertArrayHasKey('options', $data);
        $this->assertNotEmpty($data['options']);
    }

    /** @test */
    public function matching_puzzle_data_does_not_expose_pair_mapping()
    {
        $response = $this->actingAs($this->student)->getJson(
            route('puzzles.data', $this->matchPuzzle->puzzle_id)
        );

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayNotHasKey('correct_answer', $data);
        $this->assertArrayNotHasKey('pairs', $data);
        $this->assertArrayHasKey('left_items', $data);
        $this->assertArrayHasKey('right_items', $data);
    }

    // ═══════════════════════════════════════════════════════════════
    // SERVER-SIDE AUTHORITATIVE EVALUATION TESTS
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function evaluator_correctly_scores_identification_correct_answer()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $correctOptionId = $this->identPuzzle->correct_answer['correct_option'] ?? null;
        $this->assertNotNull($correctOptionId, 'Seeded puzzle must have a correct_option');

        $result = $evaluator->evaluate($this->identPuzzle, ['selected_option' => $correctOptionId]);

        $this->assertTrue($result['is_correct']);
        $this->assertEquals($this->identPuzzle->points, $result['score']);
    }

    /** @test */
    public function evaluator_scores_zero_for_wrong_identification_answer()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $result = $evaluator->evaluate($this->identPuzzle, ['selected_option' => 'opt_WRONG_9999']);

        $this->assertFalse($result['is_correct']);
        $this->assertEquals(0, $result['score']);
    }

    /** @test */
    public function evaluator_correctly_scores_matching_puzzle()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $correctPairs = $this->matchPuzzle->correct_answer['pairs'] ?? [];
        $this->assertNotEmpty($correctPairs);

        $result = $evaluator->evaluate($this->matchPuzzle, ['pairs' => $correctPairs]);

        $this->assertTrue($result['is_correct']);
        $this->assertEquals($this->matchPuzzle->points, $result['score']);
    }

    /** @test */
    public function evaluator_correctly_scores_sequencing_puzzle()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $correctOrder = $this->seqPuzzle->correct_answer['order'] ?? [];
        $this->assertNotEmpty($correctOrder);

        $result = $evaluator->evaluate($this->seqPuzzle, ['ordered_ids' => $correctOrder]);

        $this->assertTrue($result['is_correct']);
        $this->assertEquals($this->seqPuzzle->points, $result['score']);
    }

    /** @test */
    public function evaluator_correctly_scores_classification_puzzle()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $correctMapping = $this->classPuzzle->correct_answer['mapping'] ?? [];
        $this->assertNotEmpty($correctMapping);

        $result = $evaluator->evaluate($this->classPuzzle, ['placements' => $correctMapping]);

        $this->assertTrue($result['is_correct']);
        $this->assertGreaterThan(0, $result['score']);
    }

    /** @test */
    public function evaluator_correctly_scores_drag_and_drop_puzzle()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $correctPlacements = $this->dragPuzzle->correct_answer['placements'] ?? [];
        $this->assertNotEmpty($correctPlacements);

        $result = $evaluator->evaluate($this->dragPuzzle, ['placements' => $correctPlacements]);

        $this->assertTrue($result['is_correct']);
        $this->assertEquals($this->dragPuzzle->points, $result['score']);
    }

    /** @test */
    public function evaluator_correctly_scores_scenario_puzzle()
    {
        $evaluator = app(PuzzleEvaluatorService::class);

        $correctChoice = $this->scenarioPuzzle->correct_answer['correct_choice'] ?? null;
        $this->assertNotNull($correctChoice);

        $result = $evaluator->evaluate($this->scenarioPuzzle, ['choice_id' => $correctChoice]);

        $this->assertTrue($result['is_correct']);
        $this->assertEquals($this->scenarioPuzzle->points, $result['score']);
    }

    // ═══════════════════════════════════════════════════════════════
    // ANTI-TAMPERING — client cannot fake score or is_correct
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function student_cannot_submit_fake_is_correct_true_to_earn_xp()
    {
        // Even if client sends is_correct=true, the server ignores it.
        // The response score is determined server-side from the actual answer.
        $response = $this->actingAs($this->student)->postJson(
            route('puzzles.submit', $this->identPuzzle->puzzle_id),
            [
                'is_correct' => true,   // Tampered field — should be IGNORED
                'score'      => 9999,   // Tampered score — should be IGNORED
                // No selected_option submitted → evaluator will reject
            ]
        );

        $response->assertStatus(200);
        $data = $response->json();

        // Server-evaluated score must not be the tampered value
        $this->assertNotEquals(9999, $data['score'] ?? 0);
        $this->assertFalse($data['is_correct'] ?? false);
    }

    // ═══════════════════════════════════════════════════════════════
    // DRAFT PUZZLE GUARD
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function student_cannot_play_draft_puzzle()
    {
        $draftPuzzle = Puzzle::create([
            'mission_id'     => $this->mission->mission_id,
            'topic_id'       => $this->mission->topic_id,
            'puzzle_type'    => 'identification',
            'question_text'  => 'Draft Test Activity',
            'instruction'    => 'Draft testing only',
            'points'         => 50,
            'activity_data'  => ['options' => ['A', 'B']],
            'correct_answer' => ['correct_option' => 'opt_0'],
            'status'         => 'draft',
            'activity_order' => 999,
        ]);

        $response = $this->actingAs($this->student)->get(
            route('puzzles.show', $draftPuzzle->puzzle_id)
        );

        $response->assertStatus(403);

        $draftPuzzle->forceDelete();
    }

    // ═══════════════════════════════════════════════════════════════
    // LECTURER MISSION AUTHORING ACCESS
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function lecturer_can_view_missions_index()
    {
        $response = $this->actingAs($this->lecturer)->get(
            route('lecturer.missions.index')
        );

        $response->assertStatus(200);
        $response->assertSee('Missions', false);
    }

    /** @test */
    public function lecturer_can_view_mission_show_page()
    {
        $response = $this->actingAs($this->lecturer)->get(
            route('lecturer.missions.show', $this->mission->mission_id)
        );

        $response->assertStatus(200);
        $response->assertSee($this->mission->title, false);
    }

    /** @test */
    public function lecturer_can_access_puzzle_creation_form_for_each_type()
    {
        $types = ['identification', 'matching', 'sequencing', 'classification', 'drag_and_drop', 'scenario'];

        foreach ($types as $type) {
            $response = $this->actingAs($this->lecturer)->get(
                route('lecturer.missions.puzzles.create', [
                    'mission' => $this->mission->mission_id,
                    'type'    => $type,
                ])
            );

            $this->assertEquals(200, $response->status(), "Create puzzle form for type '{$type}' should return 200");
        }
    }

    /** @test */
    public function lecturer_can_preview_puzzle_without_logging_attempts()
    {
        $initialAttempts = \App\Models\PuzzleAttempt::where('puzzle_id', $this->identPuzzle->puzzle_id)->count();

        $response = $this->actingAs($this->lecturer)->get(
            route('lecturer.missions.puzzles.preview', $this->identPuzzle->puzzle_id)
        );

        $response->assertStatus(200);
        $response->assertSee('LECTURER TEST PREVIEW', false);

        // Preview must NOT log any attempts
        $afterAttempts = \App\Models\PuzzleAttempt::where('puzzle_id', $this->identPuzzle->puzzle_id)->count();
        $this->assertEquals($initialAttempts, $afterAttempts, 'Preview must not log any puzzle attempts');
    }

    /** @test */
    public function student_can_view_topic_missions_board()
    {
        $response = $this->actingAs($this->student)->get(
            route('student.topic', $this->mission->topic_id)
        );

        $response->assertStatus(200);
        $response->assertSee($this->mission->title, false);
        $response->assertSee('Learning Missions & Puzzle Challenges', false);
    }
}
