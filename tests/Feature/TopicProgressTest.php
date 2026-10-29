<?php

namespace Tests\Feature;

use App\Models\Progress;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan C5: topic progress is derived from missions cleared and the quiz. */
class TopicProgressTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    private function progress($topic): Progress
    {
        return Progress::where('user_id', $this->student->user_id)->where('topic_id', $topic->topic_id)->firstOrFail();
    }

    public function test_progress_moves_with_each_mission_cleared()
    {
        $topic = $this->makeTopic();
        $this->makeQuiz($topic);
        $puzzles = [];
        foreach ([1, 2, 3, 4] as $order) {
            $puzzles[] = $this->makeIdentification($this->makeMission($topic, $order));
        }

        $this->actingAs($this->student)->get(route('student.topic', $topic))->assertOk();
        $this->assertSame(10, (int) $this->progress($topic)->progress_percentage);

        $this->solve($puzzles[0]);
        $this->assertSame(28, (int) $this->progress($topic)->progress_percentage);
        $this->assertSame(1, (int) $this->progress($topic)->completed_missions);
    }

    public function test_topic_without_quiz_is_completed_by_its_missions()
    {
        $topic = $this->makeTopic();
        $this->solve($this->makeIdentification($this->makeMission($topic)));

        $this->assertSame(100, (int) $this->progress($topic)->progress_percentage);
        $this->assertSame('Completed', $this->progress($topic)->learning_status);
    }

    public function test_a_failed_quiz_does_not_raise_progress()
    {
        $topic = $this->makeTopic();
        $quiz = $this->makeQuiz($topic, 2, 100);
        $this->solve($this->makeIdentification($this->makeMission($topic)));
        $this->assertSame(80, (int) $this->progress($topic)->progress_percentage);

        $questions = $quiz->questions()->pluck('question_id');
        $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $questions[0], 'answer' => 'A']);
        $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $questions[1], 'answer' => 'B']);
        $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $quiz->quiz_id])->assertRedirect();

        $this->assertSame(80, (int) $this->progress($topic)->progress_percentage);
        $this->assertSame('Failed', $this->progress($topic)->quiz_status);
        $this->assertSame('In Progress', $this->progress($topic)->learning_status);
    }
}
