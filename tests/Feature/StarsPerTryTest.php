<?php

namespace Tests\Feature;

use App\Models\UserPuzzleProgress;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan C4: stars are earned per try, so a clean replay can reach 3 stars. */
class StarsPerTryTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_solve_on_third_try_gives_one_star_and_a_clean_replay_gives_three()
    {
        $mission = $this->makeMission($this->makeTopic());
        $puzzle = $this->makeIdentification($mission);

        $this->answerWrong($puzzle);
        $this->answerWrong($puzzle);
        $this->solve($puzzle)->assertJson(['stars' => 1, 'best_stars' => 1]);

        // A Mission Run replay starts a clean try
        $this->actingAs($this->student)->get(route('student.mission.run', $mission) . '?restart=1')->assertOk();
        $this->solve($puzzle)->assertJson(['stars' => 3, 'best_stars' => 3, 'earned_xp' => 0]);
    }

    public function test_a_solve_resets_the_try_even_without_restart()
    {
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));
        $this->answerWrong($puzzle);
        $this->solve($puzzle)->assertJson(['stars' => 2]);
        $this->solve($puzzle)->assertJson(['stars' => 3, 'best_stars' => 3]);
    }

    public function test_a_hint_seen_in_this_try_caps_it_at_two_stars()
    {
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));
        $this->actingAs($this->student)->postJson(route('puzzles.hint', $puzzle->puzzle_id))->assertOk();

        $this->solve($puzzle)->assertJson(['stars' => 2]);
        $this->assertFalse((bool) UserPuzzleProgress::where('puzzle_id', $puzzle->puzzle_id)->value('try_hint_used'));
    }
}
