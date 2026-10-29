<?php

namespace Tests\Feature;

use App\Models\PuzzleAttempt;
use App\Models\UserPuzzleProgress;
use App\Services\XpService;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan C2/C3/C7: puzzle XP only on solve, logged score after the hint penalty, one total. */
class XpRulesTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_wrong_partial_answer_earns_no_xp_but_logs_the_score()
    {
        $puzzle = $this->makeMatching($this->makeMission($this->makeTopic()));

        $this->answerWrong($puzzle)->assertJson(['status' => 'failed', 'earned_xp' => 0, 'score' => 50]);

        $this->assertSame(0, (int) UserPuzzleProgress::where('puzzle_id', $puzzle->puzzle_id)->value('xp_awarded'));
        $this->assertSame(50, (int) UserPuzzleProgress::where('puzzle_id', $puzzle->puzzle_id)->value('best_score'));
        $this->assertSame(0, XpService::total($this->student->user_id));
    }

    public function test_first_solve_earns_points_and_later_solves_earn_nothing()
    {
        $mission = $this->makeMission($this->makeTopic());
        $puzzle = $this->makeMatching($mission);
        $this->makeIdentification($mission, 2); // keeps the mission open so only puzzle XP counts

        $this->answerWrong($puzzle);
        $this->solve($puzzle)->assertJson(['earned_xp' => 100]);
        $this->solve($puzzle)->assertJson(['earned_xp' => 0]);

        $this->assertSame(100, XpService::total($this->student->user_id));
    }

    public function test_bought_hint_lowers_xp_and_the_logged_score_permanently()
    {
        $mission = $this->makeMission($this->makeTopic());
        $puzzle = $this->makeIdentification($mission);
        $this->makeIdentification($mission, 2); // keeps the mission open so only puzzle XP counts
        $this->actingAs($this->student)->postJson(route('puzzles.hint', $puzzle->puzzle_id))->assertJson(['charged' => true]);

        $this->solve($puzzle)->assertJson(['earned_xp' => 90, 'score' => 90]);
        $this->assertSame(90, (int) PuzzleAttempt::where('puzzle_id', $puzzle->puzzle_id)->latest('attempt_id')->value('score'));
        $this->assertSame(90, XpService::total($this->student->user_id));
    }
}
