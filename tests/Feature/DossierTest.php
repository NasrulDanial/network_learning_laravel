<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\User;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan Step 7: the lecturer dossier groups puzzle attempts and paginates both logs. */
class DossierTest extends TestCase
{
    use BuildsLearningContent;

    private User $lecturer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
        $this->lecturer = $this->makeUser('lecturer');
    }

    public function test_puzzle_attempts_are_one_row_per_puzzle_with_real_totals()
    {
        $mission = $this->makeMission($this->makeTopic());
        $retried = $this->makeIdentification($mission, 1);
        $other = $this->makeIdentification($mission, 2);
        for ($i = 0; $i < 7; $i++) {
            $this->answerWrong($retried);
        }
        $this->solve($retried);
        $this->solve($other);

        $page = $this->actingAs($this->lecturer)->get(route('lecturer.student-performance.show', $this->student))->assertOk();
        $page->assertSee('9 attempts across 2 puzzles');
        $this->assertCount(2, $page->viewData('puzzleSummary'));
        $this->assertCount(8, $page->viewData('attemptsByPuzzle')->get($retried->puzzle_id));

        $unsolved = $this->actingAs($this->lecturer)
            ->get(route('lecturer.student-performance.show', ['user' => $this->student, 'puzzle_status' => 'unsolved']));
        $this->assertCount(0, $unsolved->viewData('puzzleSummary'));
    }

    public function test_struggling_puzzles_are_flagged()
    {
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));
        foreach ([1, 2, 3] as $i) {
            $this->answerWrong($puzzle);
        }

        $this->actingAs($this->lecturer)->get(route('lecturer.student-performance.show', $this->student))
            ->assertOk()->assertSee('Struggling');
    }

    public function test_quiz_log_is_paginated_and_filterable()
    {
        $quiz = $this->makeQuiz($this->makeTopic(), 1);
        for ($i = 0; $i < 10; $i++) {
            Result::create([
                'user_id' => $this->student->user_id, 'quiz_id' => $quiz->quiz_id, 'score' => $i % 2,
                'total_questions' => 1, 'percentage' => ($i % 2) * 100, 'status' => $i % 2 ? 'Pass' : 'Fail',
                'ended_reason' => $i === 0 ? 'link_down' : 'finished', 'points_earned' => 0, 'date_taken' => now()->subMinutes($i),
            ]);
        }

        $page = $this->actingAs($this->lecturer)->get(route('lecturer.student-performance.show', $this->student))->assertOk();
        $this->assertCount(8, $page->viewData('quizAttempts'));
        $this->assertSame(10, $page->viewData('quizAttempts')->total());
        $page->assertSee('10 attempts')->assertSee('Link down');

        $passed = $this->actingAs($this->lecturer)
            ->get(route('lecturer.student-performance.show', ['user' => $this->student, 'quiz_status' => 'Pass']));
        $this->assertSame(5, $passed->viewData('quizAttempts')->total());
    }
}
