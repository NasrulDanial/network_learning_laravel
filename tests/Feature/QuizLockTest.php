<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan Step 2: the topic quiz stays locked until every mission of the topic is cleared. */
class QuizLockTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_quiz_check_and_submit_are_blocked_until_missions_are_cleared()
    {
        $topic = $this->makeTopic();
        $quiz = $this->makeQuiz($topic, 1);
        $puzzle = $this->makeIdentification($this->makeMission($topic));
        $this->makeMission($topic, 2); // empty mission: not playable, never blocks
        $qid = $quiz->questions()->value('question_id');

        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))
            ->assertRedirect(route('student.topic', ['topic' => $topic->topic_id, 'tab' => 'missions']))
            ->assertSessionHas('error', 'Clear all 1 missions to unlock the assessment (0/1 done).');
        $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $qid, 'answer' => 'B'])->assertForbidden();
        $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $quiz->quiz_id, 'answers' => [$qid => 'B']])->assertForbidden();

        $hub = $this->actingAs($this->student)->get(route('student.quiz'))->assertOk();
        $hub->assertSee('Clear missions first (0/1)')->assertSee('Go to missions');

        $this->solve($puzzle);

        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))->assertOk();
        $this->actingAs($this->student)->get(route('student.quiz'))->assertSee('Start assessment');
    }
}
