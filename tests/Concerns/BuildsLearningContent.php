<?php

namespace Tests\Concerns;

use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

/** Small fixtures for the progression, XP and quiz rule tests. */
trait BuildsLearningContent
{
    protected User $student;

    protected function setUpStudent(): void
    {
        // Seeded topics would otherwise sit in front of ours in the unlock order
        Topic::query()->update(['status' => 'draft']);
        $this->student = $this->makeUser('student');
    }

    protected function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Fixture', 'email' => $role . '_' . uniqid() . '@netlearn.test',
            'password' => Hash::make('password123'), 'role' => $role,
        ]);
    }

    protected function makeTopic(int $order = 4000): Topic
    {
        return Topic::create([
            'title' => "Fixture Topic {$order}", 'content' => 'x', 'topic_order' => $order,
            'status' => 'published', 'created_by' => $this->student->user_id,
        ]);
    }

    protected function makeMission(Topic $topic, int $order = 1, int $xp = 100): Mission
    {
        return Mission::create([
            'topic_id' => $topic->topic_id, 'title' => "Fixture Mission {$order}", 'difficulty' => 'beginner',
            'mission_order' => $order, 'pass_mark' => 50, 'xp_reward' => $xp, 'status' => 'published',
        ]);
    }

    /** One-option identification puzzle: opt_1 is right, opt_2 is wrong. */
    protected function makeIdentification(Mission $mission, int $order = 1, int $points = 100): Puzzle
    {
        return Puzzle::create([
            'mission_id' => $mission->mission_id, 'topic_id' => $mission->topic_id, 'puzzle_type' => 'identification',
            'question_text' => "Pick {$order}", 'instruction' => 'Pick one', 'hint' => 'Think about it.',
            'activity_data' => ['options' => [['id' => 'opt_1', 'label' => 'Right'], ['id' => 'opt_2', 'label' => 'Wrong']]],
            'correct_answer' => ['correct_option' => 'opt_1'], 'points' => $points,
            'activity_order' => $order, 'status' => 'published',
        ]);
    }

    /** Four-pair matching puzzle, which scores partially. */
    protected function makeMatching(Mission $mission, int $order = 1, int $points = 100): Puzzle
    {
        $left = $right = $pairs = [];
        foreach ([1, 2, 3, 4] as $i) {
            $left[] = ['id' => "L_{$i}", 'label' => "Left {$i}"];
            $right[] = ['id' => "R_{$i}", 'label' => "Right {$i}"];
            $pairs["L_{$i}"] = "R_{$i}";
        }

        return Puzzle::create([
            'mission_id' => $mission->mission_id, 'topic_id' => $mission->topic_id, 'puzzle_type' => 'matching',
            'question_text' => "Match {$order}", 'instruction' => 'Match them',
            'activity_data' => ['left_items' => $left, 'right_items' => $right],
            'correct_answer' => ['pairs' => $pairs], 'points' => $points,
            'activity_order' => $order, 'status' => 'published',
        ]);
    }

    protected function makeQuiz(Topic $topic, int $questions = 2, int $passingMark = 50): Quiz
    {
        $quiz = Quiz::create(['topic_id' => $topic->topic_id, 'title' => 'Fixture Quiz', 'passing_mark' => $passingMark]);
        for ($i = 1; $i <= $questions; $i++) {
            Question::create([
                'quiz_id' => $quiz->quiz_id, 'question_text' => "Question {$i}",
                'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'B',
            ]);
        }

        return $quiz;
    }

    protected function solve(Puzzle $puzzle): TestResponse
    {
        return $this->submitPuzzle($puzzle, true);
    }

    protected function answerWrong(Puzzle $puzzle): TestResponse
    {
        return $this->submitPuzzle($puzzle, false);
    }

    /** A wrong matching answer still gets 2 of 4 pairs right (partial score). */
    protected function submitPuzzle(Puzzle $puzzle, bool $right): TestResponse
    {
        $payload = $puzzle->puzzle_type === 'matching'
            ? ['pairs' => $right
                ? ['L_1' => 'R_1', 'L_2' => 'R_2', 'L_3' => 'R_3', 'L_4' => 'R_4']
                : ['L_1' => 'R_1', 'L_2' => 'R_2', 'L_3' => 'R_4', 'L_4' => 'R_3']]
            : ['selected_option' => $right ? 'opt_1' : 'opt_2'];

        return $this->actingAs($this->student)->postJson("/puzzles/{$puzzle->puzzle_id}/submit", $payload)->assertOk();
    }
}
