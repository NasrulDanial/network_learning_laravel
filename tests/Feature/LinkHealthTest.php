<?php

namespace Tests\Feature;

use App\Models\UserMissionProgress;
use App\Models\UserPuzzleProgress;
use App\Services\LinkHealthService;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Mission health: one pool per run, three segments = three stars, damage by severity, safe-mode floor. */
class LinkHealthTest extends TestCase
{
    use BuildsLearningContent;

    private function health($mission): int
    {
        return (int) UserMissionProgress::where('user_id', $this->student->user_id)->where('mission_id', $mission->mission_id)->value('run_health');
    }

    public function test_damage_scales_with_severity_and_escalates_with_repeat_misses()
    {
        $this->assertSame(24, LinkHealthService::damage(['correct_count' => 0, 'total' => 1], 1));
        $this->assertSame(16, LinkHealthService::damage(['correct_count' => 2, 'total' => 4], 1));
        $this->assertGreaterThanOrEqual(8, LinkHealthService::damage(['correct_count' => 3, 'total' => 4], 1));
        $this->assertSame([24, 28, 31, 35, 35], array_map(fn ($n) => LinkHealthService::damage(['correct_count' => 0, 'total' => 1], $n), [1, 2, 3, 4, 9]));
    }

    public function test_the_three_segments_are_the_three_stars()
    {
        $this->assertSame(3, LinkHealthService::stars(100));
        $this->assertSame(3, LinkHealthService::stars(67));
        $this->assertSame(2, LinkHealthService::stars(66));
        $this->assertSame(2, LinkHealthService::stars(34));
        $this->assertSame(1, LinkHealthService::stars(33));
        $this->assertSame(1, LinkHealthService::stars(10));
    }

    public function test_health_never_drops_below_the_safe_mode_floor()
    {
        $health = 100;
        $trail = [];
        foreach ([1, 2, 3, 4, 5] as $miss) {
            $r = LinkHealthService::afterMiss($health, ['correct_count' => 0, 'total' => 1], $miss);
            $health = $r['link_health'];
            $trail[] = $health;
        }
        $this->assertSame([76, 48, 17, 10, 10], $trail);
        $this->assertTrue($r['safe_mode']);
        $this->assertSame(1, $r['health_stars']);
    }

    public function test_one_wrong_answer_does_not_cost_a_star_but_accumulated_damage_does()
    {
        $this->setUpStudent();
        $mission = $this->makeMission($this->makeTopic());
        $puzzle = $this->makeIdentification($mission);
        $this->makeIdentification($mission, 2);

        $this->answerWrong($puzzle)->assertJson(['link_health' => 76, 'run_stars' => 3]);   // still 3 stars
        $this->answerWrong($puzzle)->assertJson(['link_health' => 48, 'run_stars' => 2]);   // x1.15 repeat: a star is gone
        $this->answerWrong($puzzle)->assertJson(['link_health' => 17, 'run_stars' => 1]);
    }

    public function test_health_is_one_pool_that_carries_over_to_the_next_task_of_the_mission()
    {
        $this->setUpStudent();
        $mission = $this->makeMission($this->makeTopic());
        $first = $this->makeIdentification($mission, 1);
        $second = $this->makeIdentification($mission, 2);

        $this->answerWrong($first)->assertJson(['link_health' => 76]);
        $this->solve($first)->assertJson(['link_health' => 76, 'damage' => 0]);   // solving does not refill

        // The next task opens with the same bar, and a miss there draws on it (first miss on that task: x1)
        $this->actingAs($this->student)->get("/puzzles/{$second->puzzle_id}")->assertSee('data-health="76"', false);
        $this->answerWrong($second)->assertJson(['link_health' => 52]);
        $this->assertSame(52, $this->health($mission));
    }

    public function test_mission_stars_are_the_segments_left_when_the_run_ends()
    {
        $this->setUpStudent();
        $mission = $this->makeMission($this->makeTopic());
        $a = $this->makeIdentification($mission, 1);
        $b = $this->makeIdentification($mission, 2);

        $this->answerWrong($a);
        $this->answerWrong($a); // 48 left: two stars
        $this->solve($a)->assertJson(['run_complete' => false]);
        $this->solve($b)->assertJson(['run_complete' => true, 'run_stars' => 2, 'mission_stars' => 2]);

        $this->actingAs($this->student)->getJson(route('student.mission.summary', $mission))->assertJson(['stars' => 2]);
    }

    public function test_a_restart_refills_health_and_a_better_run_raises_the_stars()
    {
        $this->setUpStudent();
        $mission = $this->makeMission($this->makeTopic());
        $puzzle = $this->makeIdentification($mission);

        $this->answerWrong($puzzle);
        $this->answerWrong($puzzle);
        $this->solve($puzzle)->assertJson(['mission_stars' => 2]);

        $this->actingAs($this->student)->get(route('student.mission.run', $mission) . '?restart=1')->assertOk();
        $this->assertSame(100, $this->health($mission));
        $this->assertFalse((bool) UserPuzzleProgress::where('puzzle_id', $puzzle->puzzle_id)->value('run_solved'));

        $this->solve($puzzle)->assertJson(['run_complete' => true, 'run_stars' => 3, 'mission_stars' => 3]);

        // A worse later run never lowers the best
        $this->actingAs($this->student)->get(route('student.mission.run', $mission) . '?restart=1');
        $this->answerWrong($puzzle);
        $this->answerWrong($puzzle);
        $this->answerWrong($puzzle);
        $this->solve($puzzle)->assertJson(['run_stars' => 1, 'mission_stars' => 3]);
    }

    public function test_lecturer_preview_applies_the_same_rules_from_the_page_state()
    {
        $this->setUpStudent();
        $lecturer = $this->makeUser('lecturer');
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));

        $this->actingAs($lecturer)
            ->withHeaders(['X-Preview-Health' => '76', 'X-Preview-Misses' => '2'])
            ->postJson(route('lecturer.missions.puzzles.preview.submit', $puzzle), ['selected_option' => 'opt_2'])
            ->assertOk()
            ->assertJson(['link_health' => 48, 'damage' => 28, 'damage_multiplier' => 1.15, 'safe_mode' => false, 'health_stars' => 2]);
    }
}
