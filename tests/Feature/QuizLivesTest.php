<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Result;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan Step 3.1–3.3: three lives per attempt; the third wrong answer ends it as a Fail. */
class QuizLivesTest extends TestCase
{
    use BuildsLearningContent;

    private Quiz $quiz;
    private array $qids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
        $this->quiz = $this->makeQuiz($this->makeTopic(), 7, 50);
        $this->qids = $this->quiz->questions()->orderBy('question_id')->pluck('question_id')->all();
    }

    private function check(int $i, string $answer)
    {
        return $this->actingAs($this->student)->postJson(route('student.quiz.check', $this->quiz), [
            'question_id' => $this->qids[$i], 'answer' => $answer,
        ]);
    }

    public function test_lives_are_three_whatever_the_quiz_length()
    {
        $this->assertSame(3, Quiz::lives());
        $long = $this->makeQuiz($this->quiz->topic, 15);
        $this->actingAs($this->student)->get(route('student.quiz.take', $this->quiz))->assertViewHas('lives', 3);
        $this->actingAs($this->student)->get(route('student.quiz.take', $long))->assertViewHas('lives', 3);
    }

    public function test_two_wrong_answers_leave_one_life_and_the_attempt_continues()
    {
        $this->check(0, 'A')->assertJson(['lives_left' => 2, 'terminated' => false]);
        $this->check(1, 'A')->assertJson(['lives_left' => 1, 'terminated' => false]);
        $this->check(2, 'B')->assertJson(['correct' => true, 'lives_left' => 1, 'terminated' => false]);
    }

    public function test_third_wrong_answer_ends_the_attempt_and_refuses_more_answers()
    {
        $this->check(0, 'A');
        $this->check(1, 'A');
        $this->check(2, 'A')->assertJson(['lives_left' => 0, 'terminated' => true]);
        $this->check(3, 'B')->assertStatus(409)->assertJson(['terminated' => true]);
    }

    public function test_link_down_is_a_fail_with_no_xp_even_above_the_pass_mark()
    {
        // 4 right, then 3 wrong: 4/7 = 57% would pass the 50% mark
        foreach ([0, 1, 2, 3] as $i) {
            $this->check($i, 'B');
        }
        foreach ([4, 5, 6] as $i) {
            $this->check($i, 'A');
        }

        $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $this->quiz->quiz_id])->assertRedirect();
        $result = Result::where('quiz_id', $this->quiz->quiz_id)->firstOrFail();
        $this->assertSame('Fail', $result->status);
        $this->assertSame('link_down', $result->ended_reason);
        $this->assertSame(0, (int) $result->points_earned);
        $this->assertSame(4, (int) $result->score);
    }

    public function test_link_down_counts_unanswered_questions_as_wrong()
    {
        $this->check(0, 'B');
        $this->check(1, 'A');
        $this->check(2, 'A');
        $this->check(3, 'A');

        $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $this->quiz->quiz_id])->assertRedirect();
        $result = Result::where('quiz_id', $this->quiz->quiz_id)->firstOrFail();
        $this->assertSame(1, (int) $result->score);
        $this->assertSame(7, (int) $result->total_questions);
        $this->assertSame('Fail', $result->status);
    }

    public function test_question_and_option_order_is_stable_on_reload_and_new_per_attempt()
    {
        $first = $this->actingAs($this->student)->get(route('student.quiz.take', $this->quiz));
        $order = $first->viewData('questions')->pluck('question_id')->all();
        $options = $first->viewData('optionOrder');

        $reload = $this->actingAs($this->student)->get(route('student.quiz.take', $this->quiz));
        $this->assertSame($order, $reload->viewData('questions')->pluck('question_id')->all());
        $this->assertSame($options, $reload->viewData('optionOrder'));
        $this->assertEqualsCanonicalizing($this->qids, $order);
        foreach ($options as $keys) {
            $this->assertEqualsCanonicalizing(['A', 'B', 'C', 'D'], $keys);
        }

        // A finished attempt starts a new one with a fresh order (7! orders: a repeat is very unlikely)
        foreach ($order as $qid) {
            $this->actingAs($this->student)->postJson(route('student.quiz.check', $this->quiz), ['question_id' => $qid, 'answer' => 'B']);
        }
        $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $this->quiz->quiz_id]);
        $changed = false;
        for ($i = 0; $i < 3 && ! $changed; $i++) {
            session()->forget('quizrun.' . $this->quiz->quiz_id);
            $next = $this->actingAs($this->student)->get(route('student.quiz.take', $this->quiz));
            $changed = $next->viewData('questions')->pluck('question_id')->all() !== $order
                || $next->viewData('optionOrder') !== $options;
        }
        $this->assertTrue($changed);
    }
}
