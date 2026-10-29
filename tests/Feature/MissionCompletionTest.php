<?php

namespace Tests\Feature;

use App\Models\UserMissionProgress;
use App\Services\XpService;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan C1: a mission is cleared only when every required puzzle is solved. */
class MissionCompletionTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_partial_scores_above_the_old_pass_mark_do_not_clear_the_mission()
    {
        $mission = $this->makeMission($this->makeTopic()); // pass_mark 50
        $this->answerWrong($this->makeMatching($mission, 1)); // 50%
        $this->answerWrong($this->makeMatching($mission, 2)); // 50%

        $this->assertFalse((bool) UserMissionProgress::where('mission_id', $mission->mission_id)->value('is_completed'));
    }

    public function test_solving_every_puzzle_clears_the_mission_and_pays_its_xp_once()
    {
        $mission = $this->makeMission($this->makeTopic(), 1, 150);
        $a = $this->makeIdentification($mission, 1);
        $b = $this->makeIdentification($mission, 2);

        $this->solve($a)->assertJson(['mission_completed' => false]);
        $this->solve($b)->assertJson(['mission_completed' => true, 'mission_xp_reward' => 150]);
        $this->solve($b)->assertJson(['mission_xp_reward' => 0]);

        $this->assertSame(350, XpService::total($this->student->user_id));
    }
}
