<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** The puzzle page's mission console: server state it renders and the data it needs from responses. */
class MissionConsoleTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_page_renders_the_console_and_no_engine_label()
    {
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));
        $this->answerWrong($puzzle);

        $page = $this->actingAs($this->student)->get("/puzzles/{$puzzle->puzzle_id}")->assertOk();
        $page->assertSee('id="mission-console"', false)
            ->assertSee('data-try-wrong="1"', false)
            ->assertSee('data-points="100"', false)
            ->assertSee('IDENTIFICATION LAB')
            ->assertDontSee('ENGINE: PHASER');
    }

    public function test_submit_and_hint_responses_carry_the_per_try_state()
    {
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));

        $this->answerWrong($puzzle)->assertJson(['try_attempts' => 1, 'try_hint_used' => false]);
        $this->actingAs($this->student)->postJson(route('puzzles.hint', $puzzle->puzzle_id))
            ->assertJson(['charged' => true, 'penalty' => 10, 'try_hint_used' => true]);
        $this->solve($puzzle)->assertJson(['try_attempts' => 0, 'try_hint_used' => false, 'earned_xp' => 90]);

        // A solved puzzle opens with the hint cost already paid and no XP at stake
        $this->actingAs($this->student)->get("/puzzles/{$puzzle->puzzle_id}")
            ->assertSee('data-solved="1"', false)
            ->assertSee('data-penalty-paid="1"', false);
    }
}
