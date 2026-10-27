<?php

namespace App\Services;

use App\Models\User;
use App\Models\Puzzle;
use App\Models\Mission;
use App\Models\PuzzleAttempt;
use App\Models\UserPuzzleProgress;
use App\Models\UserMissionProgress;
use App\Models\Progress;
use App\Models\Quiz;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class MissionProgressionService
{
    /**
     * Record a puzzle attempt, update personal best score, and award idempotent XP.
     */
    public function recordAttempt(User $user, Puzzle $puzzle, array $evaluation, ?array $rawSubmission = null): array
    {
        return DB::transaction(function () use ($user, $puzzle, $evaluation, $rawSubmission) {
            $isCorrect = (bool) $evaluation['is_correct'];
            $score = (int) $evaluation['score'];

            // 1. Log attempt into puzzle_attempts for lecturer telemetry
            $attemptNo = PuzzleAttempt::where('user_id', $user->user_id)
                ->where('puzzle_id', $puzzle->puzzle_id)
                ->count() + 1;

            $status = $isCorrect ? 'completed' : 'failed';

            $attempt = PuzzleAttempt::create([
                'user_id'    => $user->user_id,
                'puzzle_id'  => $puzzle->puzzle_id,
                'score'      => $score,
                'status'     => $status,
                'attempt_no' => $attemptNo,
                'date_taken' => now(),
            ]);

            // 2. Personal Best tracking in user_puzzle_progress (Idempotent XP protection)
            $puzzleProgress = UserPuzzleProgress::firstOrCreate(
                ['user_id' => $user->user_id, 'puzzle_id' => $puzzle->puzzle_id],
                [
                    'best_score' => 0,
                    'attempts_count' => 0,
                    'is_completed' => false,
                    'xp_awarded' => 0,
                ]
            );

            $puzzleProgress->attempts_count += 1;
            $puzzleProgress->last_attempt_at = now();

            $newXpAwarded = 0;
            if ($score > $puzzleProgress->best_score) {
                // Award incremental XP only for the personal-best delta
                $deltaScore = $score - $puzzleProgress->best_score;
                $maxPossibleXp = $puzzle->points ?: 100;
                $allowedDelta = min($deltaScore, max(0, $maxPossibleXp - $puzzleProgress->xp_awarded));

                $newXpAwarded = $allowedDelta;
                $puzzleProgress->best_score = $score;
                $puzzleProgress->xp_awarded += $newXpAwarded;
            }

            if ($isCorrect && !$puzzleProgress->is_completed) {
                $puzzleProgress->is_completed = true;
                $puzzleProgress->first_completed_at = now();
            }

            $puzzleProgress->save();

            // 3. Update global student topic progress & clearance rank
            $topicId = $puzzle->mission ? $puzzle->mission->topic_id : $puzzle->topic_id;

            $topicProgress = Progress::firstOrCreate(
                ['user_id' => $user->user_id, 'topic_id' => $topicId],
                [
                    'completed_missions' => 0,
                    'total_xp'           => 0,
                    'current_level'      => 1,
                    'current_rank'       => 'Clearance Level 1: Network Cadet',
                    'learning_status'    => 'In Progress',
                    'quiz_status'        => 'Not Attempted',
                    'progress_percentage'=> 25,
                ]
            );

            if ($newXpAwarded > 0) {
                $topicProgress->total_xp += $newXpAwarded;
            }

            // 4. Mission evaluation (Pass mark & Mission Unlock)
            $missionUnlocked = null;
            $missionCompleted = false;
            $missionXpReward = 0;

            if ($puzzle->mission) {
                $mission = $puzzle->mission;
                $missionResult = $this->evaluateMissionCompletion($user, $mission);
                $missionCompleted = $missionResult['is_completed'];
                $missionXpReward = $missionResult['xp_awarded'];
                $missionUnlocked = $missionResult['unlocked_next_mission'];

                if ($missionResult['is_newly_completed']) {
                    $topicProgress->completed_missions += 1;
                    $topicProgress->total_xp += $missionXpReward;
                }

                // Update topic progression percentage proportionally based on completed missions
                $totalMissionsInTopic = Mission::where('topic_id', $topicId)->where('status', 'published')->count();
                if ($totalMissionsInTopic > 0) {
                    $completedMissionsCount = \App\Models\UserMissionProgress::where('user_id', $user->user_id)
                        ->whereHas('mission', function ($q) use ($topicId) {
                            $q->where('topic_id', $topicId)->where('status', 'published');
                        })
                        ->where('is_completed', true)
                        ->count();

                    // Missions scale topic progression up to 75%; completing the final assessment quiz hits 100%
                    $missionProgressPct = min(75, (int) round(($completedMissionsCount / $totalMissionsInTopic) * 75));
                    if ($topicProgress->learning_status !== 'Completed') {
                        $topicProgress->progress_percentage = max((int)$topicProgress->progress_percentage, max(25, $missionProgressPct));
                        $topicProgress->learning_status = 'In Progress';
                    }
                }
            }

            // A topic without an assessment quiz is completed by finishing all of its missions
            if ($topicId && $topicProgress->learning_status !== 'Completed' && $this->allMissionsCompleted($user, $topicId) && !$this->topicHasQuiz($topicId)) {
                $topicProgress->learning_status = 'Completed';
                $topicProgress->progress_percentage = 100;
            }

            $topicProgress->save();

            // Clearance rank is global: quiz XP (results) + PBL XP (progress), same as dashboards
            $totalXp = (int) Progress::where('user_id', $user->user_id)->sum('total_xp')
                + (int) Result::where('user_id', $user->user_id)->sum('points_earned');
            $rank = RankProgressionService::getRankTelemetry($totalXp);

            Progress::where('user_id', $user->user_id)->update([
                'current_level' => $rank['current_level'],
                'current_rank'  => $rank['current_rank_title'],
            ]);

            return [
                'status'                => $status,
                'score'                 => $score,
                'max_score'             => $puzzle->points,
                'earned_xp'             => $newXpAwarded,
                'total_xp'              => $totalXp,
                'personal_best'         => $puzzleProgress->best_score,
                'clearance_level'       => $rank['current_level'],
                'clearance_rank'        => $rank['current_rank_title'],
                'mission_completed'     => $missionCompleted,
                'mission_xp_reward'     => $missionXpReward,
                'unlocked_next_mission' => $missionUnlocked ? $missionUnlocked->title : null,
                'feedback'              => $evaluation['feedback'],
                'hint'                  => $evaluation['hint'],
            ];
        });
    }

    /**
     * Evaluate whether a student has passed the mission and unlock next mission.
     */
    public function evaluateMissionCompletion(User $user, Mission $mission): array
    {
        $missionProgress = UserMissionProgress::firstOrCreate(
            ['user_id' => $user->user_id, 'mission_id' => $mission->mission_id],
            [
                'is_unlocked'           => true,
                'is_completed'          => false,
                'best_score_percentage' => 0,
                'xp_reward_granted'     => false,
            ]
        );

        $requiredPuzzles = $mission->puzzles()->where('is_required', true)->where('status', 'published')->get();
        if ($requiredPuzzles->isEmpty()) {
            // No puzzle flagged as required: every published puzzle counts, otherwise the mission could never be passed
            $requiredPuzzles = $mission->puzzles()->where('status', 'published')->get();
        }
        if ($requiredPuzzles->isEmpty()) {
            return [
                'is_completed'          => false,
                'is_newly_completed'    => false,
                'xp_awarded'            => 0,
                'unlocked_next_mission' => null,
            ];
        }

        $totalMaxPoints = $requiredPuzzles->sum('points');
        $userTotalBest = 0;
        $allAttempted = true;

        foreach ($requiredPuzzles as $p) {
            $pProg = UserPuzzleProgress::where('user_id', $user->user_id)
                ->where('puzzle_id', $p->puzzle_id)
                ->first();

            if (!$pProg || $pProg->attempts_count === 0) {
                $allAttempted = false;
            } else {
                $userTotalBest += $pProg->best_score;
            }
        }

        $aggregatePercentage = $totalMaxPoints > 0
            ? (int) round(($userTotalBest / $totalMaxPoints) * 100)
            : 0;

        if ($aggregatePercentage > $missionProgress->best_score_percentage) {
            $missionProgress->best_score_percentage = $aggregatePercentage;
        }

        $hasPassed = ($allAttempted && $aggregatePercentage >= $mission->pass_mark);
        $isNewlyCompleted = false;
        $xpAwarded = 0;
        $unlockedNext = null;

        if ($hasPassed && !$missionProgress->is_completed) {
            $missionProgress->is_completed = true;
            $missionProgress->completed_at = now();
            $isNewlyCompleted = true;

            if (!$missionProgress->xp_reward_granted) {
                $missionProgress->xp_reward_granted = true;
                $xpAwarded = (int) ($mission->xp_reward ?: 150);
            }

            // Unlock next mission in topic
            $nextMission = Mission::where('topic_id', $mission->topic_id)
                ->where('mission_order', '>', $mission->mission_order)
                ->where('status', 'published')
                ->orderBy('mission_order', 'asc')
                ->first();

            if ($nextMission) {
                $nextProg = UserMissionProgress::firstOrCreate(
                    ['user_id' => $user->user_id, 'mission_id' => $nextMission->mission_id],
                    ['is_unlocked' => true, 'is_completed' => false]
                );
                $nextProg->is_unlocked = true;
                $nextProg->save();
                $unlockedNext = $nextMission;
            }
        }

        $missionProgress->save();

        return [
            'is_completed'          => $missionProgress->is_completed,
            'is_newly_completed'    => $isNewlyCompleted,
            'xp_awarded'            => $xpAwarded,
            'unlocked_next_mission' => $unlockedNext,
        ];
    }

    private function allMissionsCompleted(User $user, int $topicId): bool
    {
        $missionIds = Mission::where('topic_id', $topicId)->where('status', 'published')->pluck('mission_id');
        if ($missionIds->isEmpty()) {
            return false;
        }

        $completed = UserMissionProgress::where('user_id', $user->user_id)
            ->whereIn('mission_id', $missionIds)
            ->where('is_completed', true)
            ->count();

        return $completed === $missionIds->count();
    }

    private function topicHasQuiz(int $topicId): bool
    {
        return Quiz::where('topic_id', $topicId)->whereHas('questions')->exists();
    }
}
