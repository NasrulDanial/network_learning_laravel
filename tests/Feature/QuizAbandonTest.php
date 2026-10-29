<?php

namespace Tests\Feature;

use App\Models\Result;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan Step 3.4: leaving mid-quiz after answering records a Fail with no XP. */
class QuizAbandonTest extends TestCase
{
    use BuildsLearningContent;

    public function test_abandon_without_answers_records_nothing()
    {
        $this->setUpStudent();
        $quiz = $this->makeQuiz($this->makeTopic(), 2);
        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))->assertOk();

        $this->actingAs($this->student)->postJson(route('student.quiz.abandon', $quiz))
            ->assertOk()->assertJson(['redirect' => route('student.quiz')]);
        $this->assertSame(0, Result::where('quiz_id', $quiz->quiz_id)->count());
    }

    public function test_abandon_with_answers_records_a_fail_and_clears_the_attempt()
    {
        $this->setUpStudent();
        $quiz = $this->makeQuiz($this->makeTopic(), 2);
        $qids = $quiz->questions()->pluck('question_id');
        $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $qids[0], 'answer' => 'B'])->assertOk();

        $this->actingAs($this->student)->postJson(route('student.quiz.abandon', $quiz))->assertOk();

        $result = Result::where('quiz_id', $quiz->quiz_id)->firstOrFail();
        $this->assertSame('Fail', $result->status);
        $this->assertSame('abandoned', $result->ended_reason);
        $this->assertSame(0, (int) $result->points_earned);
        $this->assertSame(1, (int) $result->score);
        $this->assertNull(session('quizrun.' . $quiz->quiz_id));

        // The next attempt starts clean: the old answer is not locked any more
        $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $qids[0], 'answer' => 'A'])
            ->assertJson(['locked' => false, 'answer' => 'A']);
    }
}
