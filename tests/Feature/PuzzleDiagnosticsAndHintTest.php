<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserPuzzleProgress;
use App\Services\PuzzleEvaluatorService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 1–2 of the upgrade spec: evaluator diagnostics (where an answer failed, never what is right),
 * scenario consequences, stars, and the server-enforced hint penalty.
 */
class PuzzleDiagnosticsAndHintTest extends TestCase
{
    private User $student;
    private Mission $mission;

    protected function setUp(): void
    {
        parent::setUp();
        Topic::query()->update(['status' => 'draft']);

        $this->student = User::create([
            'name' => 'Diag Student', 'email' => 'diag_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'student',
        ]);
        $topic = Topic::create(['title' => 'Diag Topic', 'content' => 'x', 'topic_order' => 2000, 'status' => 'published', 'created_by' => $this->student->user_id]);
        $this->mission = Mission::create([
            'topic_id' => $topic->topic_id, 'title' => 'Diag Mission', 'difficulty' => 'beginner',
            'mission_order' => 1, 'pass_mark' => 70, 'xp_reward' => 100, 'status' => 'published',
        ]);
    }

    private function puzzle(string $type, array $data, array $answer, array $extra = []): Puzzle
    {
        return Puzzle::create(array_merge([
            'mission_id' => $this->mission->mission_id, 'topic_id' => $this->mission->topic_id,
            'puzzle_type' => $type, 'question_text' => ucfirst($type) . ' ' . uniqid(), 'instruction' => 'Do it',
            'activity_data' => $data, 'correct_answer' => $answer, 'points' => 100,
            'activity_order' => Puzzle::where('mission_id', $this->mission->mission_id)->count() + 1,
            'hint' => 'Think about layers.', 'status' => 'published',
        ], $extra));
    }

    private function eval(Puzzle $p, array $submission): array
    {
        return app(PuzzleEvaluatorService::class)->evaluate($p, $submission);
    }

    // ── Diagnostics ──────────────────────────────────────────────────────

    public function test_sequencing_reports_first_wrong_position()
    {
        $p = $this->puzzle('sequencing',
            ['items' => [['id' => 's1', 'label' => 'A'], ['id' => 's2', 'label' => 'B'], ['id' => 's3', 'label' => 'C'], ['id' => 's4', 'label' => 'D']]],
            ['order' => ['s1', 's2', 's3', 's4']]);

        $wrong = $this->eval($p, ['ordered_ids' => ['s1', 's2', 's4', 's3']]);
        $this->assertSame('sequence', $wrong['diagnostics']['kind']);
        $this->assertSame(2, $wrong['diagnostics']['first_error']);
        $this->assertSame(2, $wrong['diagnostics']['correct_count']);

        $right = $this->eval($p, ['ordered_ids' => ['s1', 's2', 's3', 's4']]);
        $this->assertNull($right['diagnostics']['first_error']);
    }

    public function test_drag_and_drop_reports_first_wrong_zone_in_travel_order()
    {
        $p = $this->puzzle('drag_and_drop', [
            'draggables' => [['id' => 'd1', 'label' => 'Hub'], ['id' => 'd2', 'label' => 'Switch'], ['id' => 'd3', 'label' => 'Router']],
            'drop_zones' => [['id' => 'z1', 'label' => 'L1'], ['id' => 'z2', 'label' => 'L2'], ['id' => 'z3', 'label' => 'L3']],
        ], ['placements' => ['d1' => 'z1', 'd2' => 'z2', 'd3' => 'z3']]);

        // Switch and Router swapped: the packet first fails at zone z2
        $r = $this->eval($p, ['placements' => ['d1' => 'z1', 'd2' => 'z3', 'd3' => 'z2']]);
        $this->assertFalse($r['is_correct']);
        $this->assertSame('z2', $r['diagnostics']['first_error']);

        $this->assertNull($this->eval($p, ['placements' => ['d1' => 'z1', 'd2' => 'z2', 'd3' => 'z3']])['diagnostics']['first_error']);
    }

    public function test_classification_and_matching_report_first_wrong_item()
    {
        $c = $this->puzzle('classification', [
            'categories' => [['id' => 'c1', 'label' => 'X'], ['id' => 'c2', 'label' => 'Y']],
            'items' => [['id' => 'i1', 'label' => 'a'], ['id' => 'i2', 'label' => 'b'], ['id' => 'i3', 'label' => 'c']],
        ], ['mapping' => ['i1' => 'c1', 'i2' => 'c2', 'i3' => 'c1']]);
        $this->assertSame('i2', $this->eval($c, ['placements' => ['i1' => 'c1', 'i2' => 'c1', 'i3' => 'c2']])['diagnostics']['first_error']);

        $m = $this->puzzle('matching', [
            'left_items' => [['id' => 'L_1', 'label' => 'a'], ['id' => 'L_2', 'label' => 'b']],
            'right_items' => [['id' => 'R_1', 'label' => 'x'], ['id' => 'R_2', 'label' => 'y']],
        ], ['pairs' => ['L_1' => 'R_1', 'L_2' => 'R_2']]);
        $this->assertSame('L_1', $this->eval($m, ['pairs' => ['L_1' => 'R_2', 'L_2' => 'R_1']])['diagnostics']['first_error']);
    }

    public function test_diagnostics_can_be_disabled()
    {
        config(['netlearn.diagnostics_level' => 'none']);
        $p = $this->puzzle('sequencing', ['items' => [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B'], ['id' => 'c', 'label' => 'C']]], ['order' => ['a', 'b', 'c']]);

        $this->assertNull($this->eval($p, ['ordered_ids' => ['c', 'b', 'a']])['diagnostics']['first_error']);
    }

    // ── Scenario consequences never leak before submit ───────────────────

    public function test_scenario_consequence_is_only_revealed_for_the_submitted_choice()
    {
        $p = $this->puzzle('scenario', [
            'scenario_text' => 'Outage',
            'choices' => [
                ['id' => 'choice_1', 'label' => 'Reboot core', 'consequence' => '300 users lose connection for 4 minutes.'],
                ['id' => 'choice_2', 'label' => 'Check the cable', 'consequence' => 'Link restored.'],
            ],
        ], ['correct_choice' => 'choice_2']);

        $safe = json_encode($p->getSafeActivityData());
        $this->assertStringNotContainsString('consequence', $safe);
        $this->assertStringNotContainsString('300 users', $safe);
        $this->assertStringNotContainsString('correct_choice', $safe);

        $r = $this->eval($p, ['choice_id' => 'choice_1']);
        $this->assertFalse($r['is_correct']);
        $this->assertSame('300 users lose connection for 4 minutes.', $r['consequence']);

        $response = $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['choice_id' => 'choice_1'])->assertOk();
        $response->assertJsonPath('consequence', '300 users lose connection for 4 minutes.');
        $response->assertJsonPath('diagnostics.kind', 'choice');
    }

    // ── Stars and attempts ───────────────────────────────────────────────

    public function test_stars_follow_attempts_and_submit_returns_attempt_info()
    {
        $p = $this->puzzle('identification', ['options' => [['id' => 'opt_1', 'label' => 'A'], ['id' => 'opt_2', 'label' => 'B']]], ['correct_option' => 'opt_1'], ['max_attempts' => 5]);

        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_2'])
            ->assertOk()->assertJson(['stars' => 0, 'attempts_count' => 1, 'max_attempts' => 5]);
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])
            ->assertOk()->assertJson(['stars' => 2, 'attempts_count' => 2]);

        $first = $this->puzzle('identification', ['options' => [['id' => 'opt_1', 'label' => 'A'], ['id' => 'opt_2', 'label' => 'B']]], ['correct_option' => 'opt_1']);
        $this->actingAs($this->student)->postJson("/puzzles/{$first->puzzle_id}/submit", ['selected_option' => 'opt_1'])
            ->assertOk()->assertJson(['stars' => 3]);
    }

    public function test_stars_drop_to_one_after_three_failed_attempts()
    {
        $p = $this->puzzle('identification', ['options' => [['id' => 'opt_1', 'label' => 'A'], ['id' => 'opt_2', 'label' => 'B']]], ['correct_option' => 'opt_1']);
        foreach (range(1, 3) as $i) {
            $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_2'])->assertOk();
        }
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])
            ->assertOk()->assertJson(['stars' => 1]);
    }

    // ── Hints ────────────────────────────────────────────────────────────

    public function test_hint_is_charged_once_and_lowers_xp_permanently()
    {
        $p = $this->puzzle('identification', ['options' => [['id' => 'opt_1', 'label' => 'A'], ['id' => 'opt_2', 'label' => 'B']]], ['correct_option' => 'opt_1']);

        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/hint")
            ->assertOk()->assertJson(['hint' => 'Think about layers.', 'charged' => true, 'penalty' => 10]);
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/hint")
            ->assertOk()->assertJson(['charged' => false]);
        $this->assertSame(1, (int) UserPuzzleProgress::where('puzzle_id', $p->puzzle_id)->value('hints_used'));

        // First solve after the hint: 100 - 10 penalty = 90 XP, 2 stars (first try with hint)
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])
            ->assertOk()->assertJson(['earned_xp' => 90, 'stars' => 2]);

        // Replaying cannot win the penalty back
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])
            ->assertOk()->assertJson(['earned_xp' => 0]);
    }

    public function test_hint_is_free_after_two_failed_attempts_and_only_then_returned_on_failure()
    {
        $p = $this->puzzle('identification', ['options' => [['id' => 'opt_1', 'label' => 'A'], ['id' => 'opt_2', 'label' => 'B']]], ['correct_option' => 'opt_1']);

        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_2'])
            ->assertOk()->assertJson(['hint' => null, 'hint_free' => false]);
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_2'])
            ->assertOk()->assertJson(['hint' => 'Think about layers.', 'hint_free' => true]);

        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/hint")->assertOk()->assertJson(['charged' => false, 'penalty' => 0]);
        $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])
            ->assertOk()->assertJson(['earned_xp' => 100]);
    }

    public function test_lecturer_cannot_use_the_student_hint_endpoint()
    {
        $lecturer = User::create([
            'name' => 'L', 'email' => 'diag_lec_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'lecturer',
        ]);
        $p = $this->puzzle('identification', ['options' => [['id' => 'opt_1', 'label' => 'A'], ['id' => 'opt_2', 'label' => 'B']]], ['correct_option' => 'opt_1']);

        $this->actingAs($lecturer)->postJson("/puzzles/{$p->puzzle_id}/hint")->assertStatus(403);
        $this->assertSame(0, UserPuzzleProgress::where('puzzle_id', $p->puzzle_id)->count());
    }

    public function test_lecturer_authors_consequences_aligned_with_their_choices()
    {
        $lecturer = User::create([
            'name' => 'L', 'email' => 'diag_auth_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'lecturer',
        ]);

        // Middle choice row left blank: consequences must still follow their own choice
        $this->actingAs($lecturer)->post(route('lecturer.missions.puzzles.store', $this->mission), [
            'puzzle_type' => 'scenario', 'question_text' => 'Authored scenario', 'instruction' => 'Pick the fix',
            'points' => 100, 'activity_order' => 9, 'difficulty' => 'beginner', 'status' => 'published',
            'scenario_choices' => ['Reboot the core switch', '', 'Check the patch cable'],
            'scenario_consequences' => ['Everyone loses connection for 4 minutes.', 'orphan', ''],
            'correct_choice_index' => 2,
        ])->assertSessionHasNoErrors();

        $p = Puzzle::where('question_text', 'Authored scenario')->firstOrFail();
        $choices = $p->activity_data['choices'];
        $this->assertSame('Everyone loses connection for 4 minutes.', $choices[0]['consequence']);
        $this->assertArrayNotHasKey('consequence', $choices[1]);
        $this->assertSame($choices[1]['id'], $p->correct_answer['correct_choice']);
        $this->assertStringNotContainsString('Everyone loses', json_encode($p->getSafeActivityData()));
    }

    public function test_preview_submit_returns_the_same_diagnostics()
    {
        $lecturer = User::create([
            'name' => 'L', 'email' => 'diag_prev_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'lecturer',
        ]);
        $p = $this->puzzle('sequencing', ['items' => [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B'], ['id' => 'c', 'label' => 'C']]], ['order' => ['a', 'b', 'c']]);

        $this->actingAs($lecturer)->postJson(route('lecturer.missions.puzzles.preview.submit', $p), ['ordered_ids' => ['a', 'c', 'b']])
            ->assertOk()->assertJsonPath('diagnostics.first_error', 1);
        $this->assertSame(0, UserPuzzleProgress::where('puzzle_id', $p->puzzle_id)->count());
    }
}
