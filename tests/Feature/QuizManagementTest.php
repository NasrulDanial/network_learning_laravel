<?php

namespace Tests\Feature;

use App\Models\Puzzle;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    private User $lecturer;
    private User $student;
    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecturer = User::create([
            'name' => 'Quiz Test Lecturer',
            'email' => 'quiz_lecturer_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'),
            'role' => 'lecturer',
        ]);

        $this->student = User::create([
            'name' => 'Quiz Test Student',
            'email' => 'quiz_student_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
        ]);

        $this->topic = Topic::create([
            'title' => 'Quiz Library Test Topic',
            'content' => 'Test content',
            'topic_order' => (int) Topic::max('topic_order') + 1,
            'status' => 'published',
            'created_by' => $this->lecturer->user_id,
        ]);
    }

    private function makeQuiz(string $title = 'Library Test Quiz'): Quiz
    {
        return Quiz::create([
            'topic_id' => $this->topic->topic_id,
            'title' => $title,
            'passing_mark' => 70,
        ]);
    }

    public function test_quiz_library_shows_clear_actions_and_no_legacy_controls()
    {
        $this->makeQuiz();

        $response = $this->actingAs($this->lecturer)->get('/lecturer/quizzes');

        $response->assertStatus(200);
        $response->assertSee('Manage Quizzes');
        $response->assertSee('Library Test Quiz');
        $response->assertSee('Edit Questions');
        $response->assertSee('Settings');
        $response->assertDontSee('Design Board');
        $response->assertDontSee('Mission Steps');
        $response->assertDontSee('Add Quiz Challenge');
    }

    public function test_quiz_library_flags_quiz_without_questions()
    {
        $this->makeQuiz('Empty Quiz');

        $this->actingAs($this->lecturer)
            ->get('/lecturer/quizzes')
            ->assertSee('No questions — students cannot take it', false);
    }

    public function test_legacy_steps_and_board_endpoints_are_removed()
    {
        $quiz = $this->makeQuiz();
        $puzzleCount = Puzzle::count();
        $questionCount = Question::count();

        $this->actingAs($this->lecturer)->getJson("/lecturer/quizzes/{$quiz->quiz_id}/steps-data")->assertStatus(404);
        $this->actingAs($this->lecturer)->postJson("/lecturer/quizzes/{$quiz->quiz_id}/steps-data", ['instruction' => 'x'])->assertStatus(404);
        // 405: the path still matches /lecturer/puzzles/{puzzle} for PUT/DELETE, but POST is gone
        $configure = $this->actingAs($this->lecturer)->postJson('/lecturer/puzzles/configure', [
            'topic_id' => $this->topic->topic_id,
            'puzzle_type' => 'topology',
        ]);
        $this->assertContains($configure->status(), [404, 405]);
        $this->actingAs($this->lecturer)->getJson("/lecturer/puzzles/{$this->topic->topic_id}/state")->assertStatus(404);

        // No dummy questions or unsolvable board puzzles can be created any more
        $this->assertSame($puzzleCount, Puzzle::count());
        $this->assertSame($questionCount, Question::count());
    }

    public function test_lecturer_can_create_and_update_quiz_without_side_effects()
    {
        $puzzleCount = Puzzle::count();

        $this->actingAs($this->lecturer)->post('/lecturer/quizzes', [
            'topic_id' => $this->topic->topic_id,
            'title' => 'Test Automated Lab Quiz',
            'passing_mark' => 75,
        ])->assertRedirect('/lecturer/quizzes');

        $quiz = Quiz::where('title', 'Test Automated Lab Quiz')->firstOrFail();
        $this->assertEquals(75, $quiz->passing_mark);

        $quizCount = Quiz::count();
        $this->actingAs($this->lecturer)->put('/lecturer/quizzes/' . $quiz->quiz_id, [
            'topic_id' => $this->topic->topic_id,
            'title' => 'Updated Automated Lab Quiz',
            'passing_mark' => 85,
        ])->assertRedirect('/lecturer/quizzes');

        $quiz->refresh();
        $this->assertSame($quizCount, Quiz::count());
        $this->assertEquals('Updated Automated Lab Quiz', $quiz->title);
        $this->assertEquals(85, $quiz->passing_mark);

        // Quiz forms must never create or retype puzzle activities
        $this->assertSame($puzzleCount, Puzzle::count());
    }

    public function test_quiz_with_student_results_cannot_be_deleted()
    {
        $quiz = $this->makeQuiz();
        Result::create([
            'user_id' => $this->student->user_id,
            'quiz_id' => $quiz->quiz_id,
            'score' => 1,
            'total_questions' => 1,
            'percentage' => 100,
            'status' => 'Pass',
            'points_earned' => 100,
            'date_taken' => now(),
        ]);

        $this->actingAs($this->lecturer)
            ->delete('/lecturer/quizzes/' . $quiz->quiz_id)
            ->assertRedirect('/lecturer/quizzes')
            ->assertSessionHasErrors('quiz');

        $this->assertNotNull(Quiz::find($quiz->quiz_id));
        $this->assertSame(1, Result::where('quiz_id', $quiz->quiz_id)->count());
    }

    public function test_quiz_without_results_can_be_deleted()
    {
        $quiz = $this->makeQuiz();

        $this->actingAs($this->lecturer)
            ->delete('/lecturer/quizzes/' . $quiz->quiz_id)
            ->assertRedirect('/lecturer/quizzes')
            ->assertSessionHas('success');

        $this->assertNull(Quiz::find($quiz->quiz_id));
    }

    public function test_student_cannot_manage_quizzes()
    {
        $quiz = $this->makeQuiz();

        $this->actingAs($this->student)->get('/lecturer/quizzes')->assertStatus(403);
        $this->actingAs($this->student)->delete('/lecturer/quizzes/' . $quiz->quiz_id)->assertStatus(403);
        $this->assertNotNull(Quiz::find($quiz->quiz_id));
    }
}
