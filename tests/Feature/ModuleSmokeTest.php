<?php

use Tests\TestCase;

class ModuleSmokeTest extends TestCase
{
    public function test_public_pages()
    {
        $this->get('/')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }

    public function test_student_module()
    {
        $student = \App\Models\User::where('role', 'student')->first();
        if (!$student) return;

        $this->actingAs($student)->get('/student/dashboard')->assertStatus(200);
        $this->actingAs($student)->get('/student/learning')->assertStatus(200);
        $this->actingAs($student)->get('/student/quiz')->assertStatus(200);
        $this->actingAs($student)->get('/student/progress')->assertStatus(200);
    }

    public function test_lecturer_module()
    {
        $lecturer = \App\Models\User::where('role', 'lecturer')->first();
        if (!$lecturer) return;

        $this->actingAs($lecturer)->get('/lecturer/dashboard')->assertStatus(200);
        $this->actingAs($lecturer)->get('/lecturer/topics')->assertStatus(200);
        $this->actingAs($lecturer)->get('/lecturer/quizzes')->assertStatus(200);
        $this->actingAs($lecturer)->get('/lecturer/student-performance')->assertStatus(200);
    }

    public function test_puzzle_module()
    {
        $student = \App\Models\User::where('role', 'student')->first();
        $lecturer = \App\Models\User::where('role', 'lecturer')->first();
        if (!$student) return;

        // Student fetching puzzle data
        $response = $this->actingAs($student)->getJson('/puzzles/1/data');
        $response->assertStatus(200);
        $response->assertJsonStructure(['puzzle_id', 'topic_id', 'puzzle_type', 'instruction', 'points']);
        // Verify sensitive answer is NOT in response
        $this->assertArrayNotHasKey('correct_answer', $response->json());

        // Student submitting puzzle attempt
        $submit = $this->actingAs($student)->postJson('/puzzles/1/submit', [
            'is_correct' => 1,
            'score' => 150
        ]);
        $submit->assertStatus(200);
        $submit->assertJsonFragment(['status' => 'completed', 'score' => 150]);

        // Security check: Lecturer should get 403 Forbidden
        if ($lecturer) {
            $this->actingAs($lecturer)->postJson('/puzzles/1/submit', [
                'is_correct' => 1,
                'score' => 150
            ])->assertStatus(403);
        }
    }
}
