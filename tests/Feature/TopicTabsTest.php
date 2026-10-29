<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan Step 4: topic page tabs, study note sections and the assessment lock. */
class TopicTabsTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_notes_are_split_on_all_caps_headings()
    {
        $topic = $this->makeTopic();
        $topic->update(['content' => "Intro line.\n\nWHY LAYERS?\nBecause.\nOSI VERSUS TCP/IP\nFour layers, not seven."]);

        $sections = $topic->fresh()->noteSections();
        $this->assertSame(['Introduction', 'WHY LAYERS?', 'OSI VERSUS TCP/IP'], array_column($sections, 'title'));
        $this->assertSame('Four layers, not seven.', $sections[2]['body']);
    }

    public function test_topic_page_has_tabs_notes_missions_and_a_locked_assessment()
    {
        $topic = $this->makeTopic();
        $topic->update(['content' => "ENCAPSULATION\nHeaders are <added> on the way down."]);
        $this->makeQuiz($topic);
        $mission = $this->makeMission($topic);
        $this->makeIdentification($mission, 1);
        $this->makeIdentification($mission, 2);

        $page = $this->actingAs($this->student)->get(route('student.topic', ['topic' => $topic->topic_id, 'tab' => 'missions']))->assertOk();
        $page->assertSee('role="tablist"', false)
            ->assertSee('id="tab-notes"', false)
            ->assertSee('ENCAPSULATION')
            ->assertSee('Headers are &lt;added&gt; on the way down.', false) // notes stay escaped
            ->assertSee('0 of 2 tasks solved')
            ->assertDontSee('Pass: 50%')
            ->assertSee('Clear all missions to unlock (0/1).');
    }

    public function test_assessment_tab_opens_once_missions_are_cleared()
    {
        $topic = $this->makeTopic();
        $this->makeQuiz($topic);
        $this->solve($this->makeIdentification($this->makeMission($topic)));

        $this->actingAs($this->student)->get(route('student.topic', $topic))->assertOk()
            ->assertSee('3 lives: the 3rd wrong answer ends the attempt.')
            ->assertSee('Start assessment')
            ->assertDontSee('Clear all missions to unlock');
    }
}
