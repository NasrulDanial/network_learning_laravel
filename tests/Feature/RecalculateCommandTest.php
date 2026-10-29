<?php

namespace Tests\Feature;

use App\Models\Progress;
use App\Models\Result;
use App\Models\UserMissionProgress;
use App\Models\UserPuzzleProgress;
use App\Services\XpService;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** Fix plan 1.7: netlearn:recalculate repairs data recorded under the old rules. */
class RecalculateCommandTest extends TestCase
{
    use BuildsLearningContent;

    private $mission;
    private $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();

        // Data as the old rules left it: partial XP on an unsolved puzzle, a mission "cleared"
        // by percentage, and fail-then-pass quiz XP of 120.
        $topic = $this->makeTopic();
        $this->mission = $this->makeMission($topic, 1, 150);
        $puzzle = $this->makeMatching($this->mission);
        UserPuzzleProgress::create([
            'user_id' => $this->student->user_id, 'puzzle_id' => $puzzle->puzzle_id,
            'best_score' => 75, 'attempts_count' => 1, 'is_completed' => false, 'xp_awarded' => 75,
        ]);
        UserMissionProgress::create([
            'user_id' => $this->student->user_id, 'mission_id' => $this->mission->mission_id,
            'is_unlocked' => true, 'is_completed' => true, 'xp_reward_granted' => true, 'best_score_percentage' => 75,
        ]);
        Progress::create([
            'user_id' => $this->student->user_id, 'topic_id' => $topic->topic_id, 'total_xp' => 225,
            'learning_status' => 'In Progress', 'quiz_status' => 'Passed', 'progress_percentage' => 75,
        ]);

        $this->quiz = $this->makeQuiz($topic, 1);
        foreach ([['Fail', 20], ['Pass', 100]] as [$status, $points]) {
            Result::create([
                'user_id' => $this->student->user_id, 'quiz_id' => $this->quiz->quiz_id, 'score' => 0,
                'total_questions' => 1, 'percentage' => 0, 'status' => $status,
                'points_earned' => $points, 'date_taken' => now(),
            ]);
        }
    }

    public function test_dry_run_changes_nothing()
    {
        $this->artisan('netlearn:recalculate', ['--user' => $this->student->user_id, '--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame(345, XpService::total($this->student->user_id));
        $this->assertTrue((bool) UserMissionProgress::where('mission_id', $this->mission->mission_id)->value('is_completed'));
    }

    public function test_real_run_removes_over_awarded_xp()
    {
        $this->artisan('netlearn:recalculate', ['--user' => $this->student->user_id])->assertSuccessful();

        // Unsolved puzzle: 0, uncleared mission: 0, quiz capped at 100
        $this->assertSame(100, XpService::total($this->student->user_id));
        $this->assertFalse((bool) UserMissionProgress::where('mission_id', $this->mission->mission_id)->value('is_completed'));
        $this->assertSame([20, 80], Result::where('quiz_id', $this->quiz->quiz_id)->orderBy('result_id')->pluck('points_earned')->map(fn ($p) => (int) $p)->all());

        $progress = Progress::where('user_id', $this->student->user_id)->first();
        $this->assertSame(10, (int) $progress->progress_percentage);
        $this->assertSame('In Progress', $progress->learning_status);
    }
}
