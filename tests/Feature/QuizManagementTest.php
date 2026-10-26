<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Topic;
use App\Models\Quiz;
use App\Models\Puzzle;

class QuizManagementTest extends TestCase
{
    public function test_lecturer_can_view_quizzes_management()
    {
        $lecturer = User::where('role', 'lecturer')->first();
        if (!$lecturer) return;

        $response = $this->actingAs($lecturer)->get('/lecturer/quizzes');
        $response->assertStatus(200);
        $response->assertSee('Manage Quiz Challenges & Missions', false);
        $response->assertSee('Steps');
        $response->assertSee('Settings');
        $response->assertSee('Design Board');
    }

    public function test_mission_steps_api()
    {
        $lecturer = User::where('role', 'lecturer')->first();
        $quiz = Quiz::first();
        if (!$lecturer || !$quiz) return;

        // 1. Get steps
        $getResponse = $this->actingAs($lecturer)->getJson("/lecturer/quizzes/{$quiz->quiz_id}/steps-data");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonStructure(['status', 'quiz', 'steps']);

        // 2. Add step
        $addResponse = $this->actingAs($lecturer)->postJson("/lecturer/quizzes/{$quiz->quiz_id}/steps-data", [
            'instruction' => 'Connect Router 1 to Distribution Switch 01'
        ]);
        $addResponse->assertStatus(200);
        $addResponse->assertJsonFragment(['status' => 'success']);
        $questionId = $addResponse->json('step.question_id');

        // 3. Delete step
        $delResponse = $this->actingAs($lecturer)->deleteJson("/lecturer/quizzes/{$quiz->quiz_id}/steps-data/{$questionId}");
        $delResponse->assertStatus(200);
        $delResponse->assertJsonFragment(['status' => 'success']);
    }

    public function test_lecturer_can_create_and_update_quiz()
    {
        $lecturer = User::where('role', 'lecturer')->first();
        $topic = Topic::first();
        if (!$lecturer || !$topic) return;

        // 1. Create a quiz
        $createResponse = $this->actingAs($lecturer)->post('/lecturer/quizzes', [
            'topic_id' => $topic->topic_id,
            'title' => 'Test Automated Lab Quiz',
            'passing_mark' => 75,
            'scenario' => 'Test scenario narrative',
            'puzzle_category' => 'topology',
            'xp_points' => 200,
        ]);
        $createResponse->assertRedirect('/lecturer/quizzes');

        $quiz = Quiz::where('title', 'Test Automated Lab Quiz')->first();
        $this->assertNotNull($quiz);
        $this->assertEquals(75, $quiz->passing_mark);

        // 2. Update the quiz (ensure PUT updates and does not duplicate)
        $initialCount = Quiz::count();
        $updateResponse = $this->actingAs($lecturer)->put('/lecturer/quizzes/' . $quiz->quiz_id, [
            'topic_id' => $topic->topic_id,
            'title' => 'Updated Automated Lab Quiz',
            'passing_mark' => 85,
            'scenario' => 'Updated scenario',
            'puzzle_category' => 'subnet',
            'xp_points' => 250,
        ]);
        $updateResponse->assertRedirect('/lecturer/quizzes');

        $this->assertEquals($initialCount, Quiz::count());
        $quiz->refresh();
        $this->assertEquals('Updated Automated Lab Quiz', $quiz->title);
        $this->assertEquals(85, $quiz->passing_mark);

        // Clean up
        $quiz->delete();
    }

    public function test_lecturer_can_configure_puzzle_board()
    {
        $lecturer = User::where('role', 'lecturer')->first();
        $topic = Topic::first();
        if (!$lecturer || !$topic) return;

        $response = $this->actingAs($lecturer)->postJson('/lecturer/puzzles/configure', [
            'topic_id' => $topic->topic_id,
            'puzzle_type' => 'topology',
            'points' => 225,
            'instruction' => 'Unit test topology setup',
            'correct_answer' => [
                'gateway_ip' => '192.168.1.1',
                'host_ip' => '192.168.1.100',
                'subnet_mask' => '255.255.255.0'
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'puzzle' => [
                'topic_id' => $topic->topic_id,
                'puzzle_type' => 'drag_and_drop',
                'points' => 225,
            ]
        ]);

        $puzzle = Puzzle::where('topic_id', $topic->topic_id)->where('puzzle_type', 'drag_and_drop')->first();
        $this->assertNotNull($puzzle);
        $this->assertEquals('drag_and_drop', $puzzle->puzzle_type);
        $this->assertEquals(225, $puzzle->points);
    }

    public function test_student_cannot_configure_puzzle_board()
    {
        $student = User::where('role', 'student')->first();
        $topic = Topic::first();
        if (!$student || !$topic) return;

        $response = $this->actingAs($student)->postJson('/lecturer/puzzles/configure', [
            'topic_id' => $topic->topic_id,
            'puzzle_type' => 'topology',
            'points' => 200,
        ]);

        $response->assertStatus(403);
    }
}
