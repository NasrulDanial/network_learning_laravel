<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Phase 6 of the upgrade spec: lecturer-authored story ticket fields for Mission Run. */
class MissionStoryFieldsTest extends TestCase
{
    private User $lecturer;
    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lecturer = User::create([
            'name' => 'Story Lecturer', 'email' => 'story_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'lecturer',
        ]);
        $this->topic = Topic::create([
            'title' => 'Story Topic', 'content' => 'x', 'topic_order' => 3100,
            'status' => 'draft', 'created_by' => $this->lecturer->user_id,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'topic_id' => $this->topic->topic_id, 'title' => 'Story Mission', 'difficulty' => 'beginner',
            'mission_order' => 1, 'pass_mark' => 70, 'xp_reward' => 100, 'status' => 'draft',
        ], $extra);
    }

    public function test_story_fields_are_saved_on_create_and_update()
    {
        $this->actingAs($this->lecturer)->post(route('lecturer.missions.store'), $this->payload([
            'ticket_code' => '#0142', 'narrator' => 'NOC Lead',
            'briefing_text' => 'Lab Block B is offline.', 'debrief_text' => 'Lab Block B restored.',
        ]))->assertSessionHasNoErrors();

        $mission = Mission::where('topic_id', $this->topic->topic_id)->firstOrFail();
        $this->assertSame('#0142', $mission->ticket_code);
        $this->assertSame('NOC Lead', $mission->narrator);
        $this->assertSame('Lab Block B is offline.', $mission->briefing_text);

        $this->actingAs($this->lecturer)->put(route('lecturer.missions.update', $mission), $this->payload([
            'ticket_code' => '', 'narrator' => '', 'briefing_text' => 'Updated briefing.', 'debrief_text' => '',
        ]))->assertSessionHasNoErrors();

        $mission->refresh();
        $this->assertNull($mission->ticket_code);
        $this->assertNull($mission->narrator);
        $this->assertSame('Updated briefing.', $mission->briefing_text);
    }

    public function test_story_fields_are_length_limited()
    {
        $this->actingAs($this->lecturer)->post(route('lecturer.missions.store'), $this->payload([
            'ticket_code' => str_repeat('X', 21), 'narrator' => str_repeat('N', 61),
            'briefing_text' => str_repeat('b', 501), 'debrief_text' => str_repeat('d', 301),
        ]))->assertSessionHasErrors(['ticket_code', 'narrator', 'briefing_text', 'debrief_text']);
    }

    public function test_forms_show_the_story_ticket_section()
    {
        $this->actingAs($this->lecturer)->get(route('lecturer.missions.create'))->assertOk()->assertSee('Story ticket')->assertSee('name="briefing_text"', false);

        $mission = Mission::create($this->payload(['ticket_code' => '#0777']));
        $this->actingAs($this->lecturer)->get(route('lecturer.missions.edit', $mission))->assertOk()->assertSee('value="#0777"', false);
    }
}
