<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Phase 4 of the upgrade spec: Mission Run access, resume point, summary and the run_mode flag. */
class MissionRunTest extends TestCase
{
    private User $student;
    private Topic $topic;
    private Mission $m1;
    private Mission $m2;
    private array $puzzles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Topic::query()->update(['status' => 'draft']);

        $this->student = User::create([
            'name' => 'Run Student', 'email' => 'run_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'student',
        ]);
        $this->topic = Topic::create([
            'title' => 'Run Topic', 'content' => 'x', 'topic_order' => 3000,
            'status' => 'published', 'created_by' => $this->student->user_id,
        ]);
        $this->m1 = $this->mission(1, 'Run Mission One', ['ticket_code' => '#0142', 'briefing_text' => 'Lab Block B is offline.']);
        $this->m2 = $this->mission(2, 'Run Mission Two');
        foreach ([1, 2] as $order) {
            $this->puzzles[] = $this->puzzle($this->m1, $order);
        }
        $this->puzzle($this->m2, 1);
    }

    private function mission(int $order, string $title, array $extra = []): Mission
    {
        return Mission::create(array_merge([
            'topic_id' => $this->topic->topic_id, 'title' => $title, 'difficulty' => 'beginner',
            'mission_order' => $order, 'pass_mark' => 70, 'xp_reward' => 100, 'status' => 'published',
        ], $extra));
    }

    private function puzzle(Mission $mission, int $order): Puzzle
    {
        return Puzzle::create([
            'mission_id' => $mission->mission_id, 'topic_id' => $mission->topic_id, 'puzzle_type' => 'identification',
            'question_text' => "{$mission->title} task {$order}", 'instruction' => 'Pick one',
            'activity_data' => ['options' => [['id' => 'opt_1', 'label' => 'Right'], ['id' => 'opt_2', 'label' => 'Wrong']]],
            'correct_answer' => ['correct_option' => 'opt_1'], 'points' => 100,
            'activity_order' => $order, 'status' => 'published',
        ]);
    }

    public function test_unlocked_mission_run_renders_with_story_and_tasks()
    {
        $response = $this->actingAs($this->student)->get(route('student.mission.run', $this->m1))->assertOk();
        $config = $response->viewData('runConfig');

        $this->assertSame('#0142', $config['ticketCode']);
        $this->assertSame('Lab Block B is offline.', $config['briefing']);
        $this->assertSame('Senior Network Engineer', $config['narrator']);
        $this->assertCount(2, $config['puzzles']);
        $this->assertStringEndsWith('?run=1', $config['puzzles'][0]['url']);
        $this->assertSame('server', $config['puzzles'][1]['device']);
        $this->assertSame(0, $config['startIndex']);
    }

    public function test_locked_mission_redirects_to_topic()
    {
        $this->actingAs($this->student)->get(route('student.mission.run', $this->m2))
            ->assertRedirect(route('student.topic', $this->topic))
            ->assertSessionHas('error');
    }

    public function test_run_resumes_at_first_unsolved_task_and_restart_starts_over()
    {
        $this->actingAs($this->student)->postJson("/puzzles/{$this->puzzles[0]->puzzle_id}/submit", ['selected_option' => 'opt_1'])->assertOk();

        $config = $this->actingAs($this->student)->get(route('student.mission.run', $this->m1))->viewData('runConfig');
        $this->assertSame(1, $config['startIndex']);
        $this->assertTrue($config['puzzles'][0]['completed']);

        $restart = $this->actingAs($this->student)->get(route('student.mission.run', $this->m1) . '?restart=1')->viewData('runConfig');
        $this->assertSame(0, $restart['startIndex']);
    }

    public function test_summary_reports_stars_xp_and_next_mission()
    {
        foreach ($this->puzzles as $p) {
            $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])->assertOk();
        }

        $this->actingAs($this->student)->getJson(route('student.mission.summary', $this->m1))
            ->assertOk()
            ->assertJson([
                'completed' => true,
                'stars' => 3,
                'puzzleStars' => [3, 3],
                'totalXpThisMission' => 300, // 2 x 100 puzzle XP + 100 mission reward
                'ticketCode' => '#0142',
                'nextMission' => ['title' => 'Run Mission Two'],
            ]);
    }

    public function test_lecturer_cannot_open_a_student_run()
    {
        $lecturer = User::create([
            'name' => 'L', 'email' => 'run_lec_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'lecturer',
        ]);
        $this->actingAs($lecturer)->get(route('student.mission.run', $this->m1))->assertStatus(403);
    }

    public function test_disabling_run_mode_restores_the_single_puzzle_flow()
    {
        $this->actingAs($this->student)->get(route('student.topic', $this->topic))->assertOk()->assertSee('Start mission');

        config(['netlearn.run_mode' => false]);
        $this->actingAs($this->student)->get(route('student.mission.run', $this->m1))->assertNotFound();
        $page = $this->actingAs($this->student)->get(route('student.topic', $this->topic))->assertOk();
        $page->assertDontSee('Start mission');
        $page->assertSee('Launch Puzzle');

        // Puzzle pages ignore ?run=1 when run mode is off
        $this->actingAs($this->student)->get("/puzzles/{$this->puzzles[0]->puzzle_id}?run=1")->assertOk()->assertViewHas('inRun', false);
    }

    public function test_topology_board_shows_states_and_names_the_unlocking_mission()
    {
        $page = $this->actingAs($this->student)->get(route('student.topic', $this->topic))->assertOk();
        $page->assertSee('Network path');
        $page->assertSee('0/2 segments up');
        $page->assertSee('topo-node is-active', false);
        $page->assertSee('topo-node is-locked', false);
        $page->assertSee('Complete “Run Mission One” to unlock', false);
        $page->assertDontSee('End-to-end connectivity established');

        foreach ([...$this->puzzles, ...Puzzle::where('mission_id', $this->m2->mission_id)->get()] as $p) {
            $this->actingAs($this->student)->postJson("/puzzles/{$p->puzzle_id}/submit", ['selected_option' => 'opt_1'])->assertOk();
        }

        $done = $this->actingAs($this->student)->get(route('student.topic', $this->topic))->assertOk();
        $done->assertSee('2/2 segments up');
        $done->assertSee('End-to-end connectivity established');
        $done->assertDontSee('topo-node is-locked', false);
    }
}
