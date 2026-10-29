<?php

namespace Tests\Feature;

use App\Models\Result;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan C6: quiz XP is capped per quiz, so failing first never pays more than passing first. */
class QuizXpCapTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    private function attempt($quiz, string $answer): void
    {
        foreach ($quiz->questions()->pluck('question_id') as $qid) {
            $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $qid, 'answer' => $answer])->assertOk();
        }
        $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $quiz->quiz_id])->assertRedirect();
    }

    public function test_fail_then_pass_totals_the_quiz_cap()
    {
        $quiz = $this->makeQuiz($this->makeTopic(), 1);

        $this->attempt($quiz, 'A'); // fail: 20
        $this->attempt($quiz, 'B'); // pass: the remaining 80
        $this->attempt($quiz, 'B'); // pass again: 0

        $points = Result::where('quiz_id', $quiz->quiz_id)->orderBy('result_id')->pluck('points_earned')->map(fn ($p) => (int) $p)->all();
        $this->assertSame([20, 80, 0], $points);
    }

    public function test_quiz_header_shows_the_xp_still_claimable()
    {
        $quiz = $this->makeQuiz($this->makeTopic(), 1);
        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))->assertOk()->assertSee('+100 Network XP');

        $this->attempt($quiz, 'A');
        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))->assertSee('+80 Network XP');

        $this->attempt($quiz, 'B');
        $this->actingAs($this->student)->get(route('student.quiz.take', $quiz))->assertSee('XP already claimed');
    }
}
