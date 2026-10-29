<?php

namespace Tests\Feature;

use App\Models\UserPuzzleProgress;
use App\Services\LinkHealthService;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Link health: damage scales with severity, escalates with repeat misses, has a safe-mode floor. */
class LinkHealthTest extends TestCase
{
    use BuildsLearningContent;

    public function test_damage_scales_with_how_wrong_the_answer_was()
    {
        $nothingRight = LinkHealthService::damage(['correct_count' => 0, 'total' => 4], 1);
        $halfRight = LinkHealthService::damage(['correct_count' => 2, 'total' => 4], 1);
        $almost = LinkHealthService::damage(['correct_count' => 3, 'total' => 4], 1);

        $this->assertSame(24, $nothingRight);
        $this->assertGreaterThan($almost, $halfRight);
        $this->assertGreaterThan($halfRight, $nothingRight);
        $this->assertGreaterThanOrEqual(8, $almost); // a near miss still costs about a third
        $this->assertSame(24, LinkHealthService::damage(['correct_count' => 0, 'total' => 1], 1)); // all-or-nothing tasks
        $this->assertSame(24, LinkHealthService::damage(null, 1));
    }

    public function test_repeat_misses_escalate_up_to_a_cap()
    {
        $diag = ['correct_count' => 0, 'total' => 1];
        $this->assertSame([24, 28, 31, 35, 35], array_map(fn ($n) => LinkHealthService::damage($diag, $n), [1, 2, 3, 4, 9]));
        $this->assertEquals(1.0, LinkHealthService::escalation(1));
        $this->assertEquals(1.45, LinkHealthService::escalation(50));
    }

    public function test_unlimited_tasks_never_drop_below_the_safe_mode_floor()
    {
        $health = 100;
        $trail = [];
        foreach ([1, 2, 3, 4, 5, 6] as $miss) {
            $r = LinkHealthService::afterMiss($health, ['correct_count' => 0, 'total' => 1], $miss, 0, $miss);
            $health = $r['link_health'];
            $trail[] = $health;
        }
        $this->assertSame([76, 48, 17, 10, 10, 10], $trail);
        $this->assertTrue($r['safe_mode']);
        $this->assertSame(0, $r['damage']); // already at the floor
    }

    public function test_limited_tasks_show_attempts_left_as_health()
    {
        $one = LinkHealthService::afterMiss(100, ['correct_count' => 0, 'total' => 1], 1, 4, 1);
        $last = LinkHealthService::afterMiss(25, ['correct_count' => 0, 'total' => 1], 3, 4, 4);

        $this->assertSame(75, $one['link_health']);
        $this->assertSame(0, $last['link_health']);
        $this->assertFalse($last['safe_mode']);
    }

    public function test_submit_responses_persist_health_and_a_solve_refills_it()
    {
        $this->setUpStudent();
        $match = $this->makeMatching($this->makeMission($this->makeTopic()));

        // 2 of 4 pairs right: half-severity damage
        $first = $this->answerWrong($match)->json();
        $this->assertSame(['link_health' => 84, 'damage' => 16, 'damage_multiplier' => 1, 'safe_mode' => false],
            ['link_health' => $first['link_health'], 'damage' => $first['damage'], 'damage_multiplier' => $first['damage_multiplier'], 'safe_mode' => $first['safe_mode']]);

        $second = $this->answerWrong($match)->json();
        $this->assertSame(84 - 19, $second['link_health']); // the repeat miss hits harder
        $this->assertSame(65, (int) UserPuzzleProgress::where('puzzle_id', $match->puzzle_id)->value('link_health'));

        // The page opens with the stored health
        $this->actingAs($this->student)->get("/puzzles/{$match->puzzle_id}")->assertSee('data-health="65"', false);

        $this->solve($match)->assertJson(['link_health' => 100, 'damage' => 0]);
        $this->assertSame(100, (int) UserPuzzleProgress::where('puzzle_id', $match->puzzle_id)->value('link_health'));
    }

    public function test_a_mission_restart_refills_health()
    {
        $this->setUpStudent();
        $mission = $this->makeMission($this->makeTopic());
        $puzzle = $this->makeIdentification($mission);
        $this->answerWrong($puzzle);
        $this->assertSame(76, (int) UserPuzzleProgress::where('puzzle_id', $puzzle->puzzle_id)->value('link_health'));

        $this->actingAs($this->student)->get(route('student.mission.run', $mission) . '?restart=1')->assertOk();
        $this->assertSame(100, (int) UserPuzzleProgress::where('puzzle_id', $puzzle->puzzle_id)->value('link_health'));
    }

    public function test_lecturer_preview_applies_the_same_rules_from_the_page_state()
    {
        $this->setUpStudent();
        $lecturer = $this->makeUser('lecturer');
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));

        $response = $this->actingAs($lecturer)
            ->withHeaders(['X-Preview-Health' => '76', 'X-Preview-Misses' => '2'])
            ->postJson(route('lecturer.missions.puzzles.preview.submit', $puzzle), ['selected_option' => 'opt_2'])
            ->assertOk();

        $response->assertJson(['link_health' => 48, 'damage' => 28, 'damage_multiplier' => 1.15, 'safe_mode' => false]);
        $this->assertSame(0, UserPuzzleProgress::count() - UserPuzzleProgress::where('user_id', '!=', $lecturer->user_id)->count());
    }
}
