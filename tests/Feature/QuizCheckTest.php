<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Phase 7 of the upgrade spec: instant per-question check with server-locked answers. */
class QuizCheckTest extends TestCase
{
    private User $student;
    private Quiz $quiz;
    private Question $q1;
    private Question $q2;

    protected function setUp(): void
    {
        parent::setUp();
        Topic::query()->update(['status' => 'draft']);

        $this->student = User::create([
            'name' => 'Quiz Student', 'email' => 'quiz_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'student',
        ]);
        $topic = Topic::create([
            'title' => 'Quiz Topic', 'content' => 'x', 'topic_order' => 3200,
            'status' => 'published', 'created_by' => $this->student->user_id,
        ]);
        $this->quiz = Quiz::create(['topic_id' => $topic->topic_id, 'title' => 'Check Quiz', 'passing_mark' => 50]);
        $this->q1 = $this->question('Which layer routes packets?', 'B', 'Layer 3 (Network) chooses paths using IP addresses.');
        $this->q2 = $this->question('Which device floods unknown frames?', 'C', null);
    }

    private function question(string $text, string $correct, ?string $explanation): Question
    {
        return Question::create([
            'quiz_id' => $this->quiz->quiz_id, 'question_text' => $text,
            'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd',
            'correct_answer' => $correct, 'explanation' => $explanation,
        ]);
    }

    private function check(Question $q, string $answer)
    {
        return $this->actingAs($this->student)->postJson(route('student.quiz.check', $this->quiz), [
            'question_id' => $q->question_id, 'answer' => $answer,
        ]);
    }

    public function test_check_returns_verdict_and_explanation()
    {
        $this->check($this->q1, 'B')->assertOk()->assertExactJson([
            'question_id' => $this->q1->question_id, 'answer' => 'B', 'correct' => true, 'correct_answer' => 'B',
            'explanation' => 'Layer 3 (Network) chooses paths using IP addresses.', 'locked' => false,
            'lives' => 3, 'lives_left' => 3, 'terminated' => false,
        ]);
        $this->check($this->q2, 'A')->assertOk()->assertJson(['correct' => false, 'correct_answer' => 'C', 'explanation' => null]);
    }

    public function test_answer_cannot_be_changed_after_checking()
    {
        $this->check($this->q1, 'A')->assertJson(['correct' => false]);
        $this->check($this->q1, 'B')->assertOk()->assertJson(['answer' => 'A', 'correct' => false, 'locked' => true]);
    }

    public function test_submit_uses_locked_answers_and_clears_the_attempt()
    {
        $this->check($this->q1, 'A'); // wrong, locked
        $this->check($this->q2, 'C'); // right, locked

        // Posting the right answer for q1 after seeing the verdict must not count
        $this->actingAs($this->student)->post(route('student.quiz.submit'), [
            'quiz_id' => $this->quiz->quiz_id,
            'answers' => [$this->q1->question_id => 'B', $this->q2->question_id => 'C'],
        ])->assertRedirect();

        $result = Result::where('user_id', $this->student->user_id)->latest('result_id')->firstOrFail();
        $this->assertSame(1, (int) $result->score);
        $this->assertNull(session('quizrun.' . $this->quiz->quiz_id));

        // A fresh attempt starts unlocked
        $this->check($this->q1, 'B')->assertJson(['correct' => true, 'locked' => false]);
    }

    public function test_quiz_page_restores_locked_answers_without_leaking_unchecked_ones()
    {
        $this->check($this->q1, 'A');
        $page = $this->actingAs($this->student)->get(route('student.quiz.take', $this->quiz))->assertOk();
        $checked = $page->viewData('checked');

        $this->assertSame('A', $checked[$this->q1->question_id]['answer']);
        $this->assertArrayNotHasKey($this->q2->question_id, $checked);
        // The page only carries verdicts for locked questions
        preg_match("/data-checked='([^']*)'/", $page->getContent(), $m);
        $embedded = json_decode($m[1], true);
        $this->assertSame([(string) $this->q1->question_id], array_map('strval', array_keys($embedded)));
    }

    public function test_check_rejects_questions_from_other_quizzes_and_locked_topics()
    {
        $other = Quiz::create(['topic_id' => $this->quiz->topic_id, 'title' => 'Other', 'passing_mark' => 50]);
        $foreign = Question::create([
            'quiz_id' => $other->quiz_id, 'question_text' => 'x',
            'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'A',
        ]);
        $this->check($foreign, 'A')->assertNotFound();
        $this->check($this->q1, 'E')->assertStatus(422);

        Topic::where('topic_id', $this->quiz->topic_id)->update(['status' => 'draft']);
        $this->check($this->q1, 'B')->assertForbidden();
    }

    public function test_lecturer_can_author_question_explanations()
    {
        $lecturer = User::create([
            'name' => 'L', 'email' => 'quiz_lec_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => 'lecturer',
        ]);
        $this->actingAs($lecturer)->post(route('lecturer.questions.store', $this->quiz), [
            'question_text' => 'New Q', 'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd',
            'correct_answer' => 'D', 'explanation' => 'Because D.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Because D.', Question::where('question_text', 'New Q')->value('explanation'));

        $this->actingAs($lecturer)->post(route('lecturer.questions.store', $this->quiz), [
            'question_text' => 'Long', 'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd',
            'correct_answer' => 'D', 'explanation' => str_repeat('x', 601),
        ])->assertSessionHasErrors('explanation');
    }
}
