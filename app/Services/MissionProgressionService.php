<?php

namespace App\Services;

use App\Models\User;
use App\Models\Puzzle;
use App\Models\Mission;
use App\Models\PuzzleAttempt;
use App\Models\UserPuzzleProgress;
use App\Models\UserMissionProgress;
use App\Models\Progress;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class MissionProgressionService
{
    /**
     * Record a puzzle attempt.
     *
     * XP is earned once, when the puzzle is first solved: its points minus the hint penalty if a
     * hint was ever bought. Wrong answers earn nothing (their partial score is still logged for
     * lecturer analytics). Stars are earned per try, so a clean replay can raise them.
     */
    public function recordAttempt(User $user, Puzzle $puzzle, array $evaluation, ?array $rawSubmission = null): array
    {
        return DB::transaction(function () use ($user, $puzzle, $evaluation, $rawSubmission) {
            $isCorrect = (bool) $evaluation['is_correct'];
            $rawScore = (int) $evaluation['score'];
            $status = $isCorrect ? 'completed' : 'failed';

            $puzzleProgress = UserPuzzleProgress::firstOrCreate(
                ['user_id' => $user->user_id, 'puzzle_id' => $puzzle->puzzle_id],
                [
                    'best_score' => 0,
                    'attempts_count' => 0,
                    'is_completed' => false,
                    'xp_awarded' => 0,
                ]
            );

            // A paid hint permanently lowers what this activity is worth, so the logged score,
            // the best score and the XP all agree.
            $penalty = (int) $puzzleProgress->hints_used > 0 ? (int) config('netlearn.hint_xp_penalty') : 0;
            $score = max(0, $rawScore - $penalty);

            $attemptNo = PuzzleAttempt::where('user_id', $user->user_id)
                ->where('puzzle_id', $puzzle->puzzle_id)
                ->count() + 1;

            PuzzleAttempt::create([
                'user_id'    => $user->user_id,
                'puzzle_id'  => $puzzle->puzzle_id,
                'score'      => $score,
                'status'     => $status,
                'attempt_no' => $attemptNo,
                'date_taken' => now(),
            ]);

            $puzzleProgress->attempts_count += 1;
            $puzzleProgress->try_attempts = (int) $puzzleProgress->try_attempts + 1;
            $puzzleProgress->last_attempt_at = now();
            $puzzleProgress->best_score = max((int) $puzzleProgress->best_score, $score); // analytics only

            $newXpAwarded = 0;
            $stars = 0;
            if ($isCorrect) {
                $maxXp = max(0, (int) ($puzzle->points ?: 100) - $penalty);
                $newXpAwarded = max(0, $maxXp - (int) $puzzleProgress->xp_awarded);
                $puzzleProgress->xp_awarded += $newXpAwarded;

                // Stars for this try: 3 = first try without a hint bought in this try,
                // 2 = within two tries, 1 = later. Only the best is kept; the try then resets.
                $tries = (int) $puzzleProgress->try_attempts;
                $stars = ($tries === 1 && ! $puzzleProgress->try_hint_used) ? 3 : ($tries <= 2 ? 2 : 1);
                $puzzleProgress->best_stars = max((int) $puzzleProgress->best_stars, $stars);
                $puzzleProgress->try_attempts = 0;
                $puzzleProgress->try_hint_used = false;

                if (! $puzzleProgress->is_completed) {
                    $puzzleProgress->is_completed = true;
                    $puzzleProgress->first_completed_at = now();
                }
            }

            $puzzleProgress->save();

            $topicId = $puzzle->mission ? $puzzle->mission->topic_id : $puzzle->topic_id;
            $topicProgress = $topicId
                ? Progress::firstOrCreate(['user_id' => $user->user_id, 'topic_id' => $topicId], ['total_xp' => 0])
                : null;

            if ($topicProgress && $newXpAwarded > 0) {
                $topicProgress->total_xp += $newXpAwarded;
            }

            // Mission completion and unlocking of the next mission
            $missionUnlocked = null;
            $missionCompleted = false;
            $missionXpReward = 0;

            if ($puzzle->mission) {
                $missionResult = $this->evaluateMissionCompletion($user, $puzzle->mission);
                $missionCompleted = $missionResult['is_completed'];
                $missionXpReward = $missionResult['xp_awarded'];
                $missionUnlocked = $missionResult['unlocked_next_mission'];

                if ($topicProgress && $missionXpReward > 0) {
                    $topicProgress->total_xp += $missionXpReward;
                }
            }

            if ($topicProgress) {
                $topicProgress->save();
                $topic = Topic::find($topicId);
                if ($topic) {
                    app(TopicProgressService::class)->recalculate($user->user_id, $topic);
                }
            }

            $totalXp = XpService::total($user->user_id);
            $rank = RankProgressionService::getRankTelemetry($totalXp);

            $hintFree = (int) $puzzleProgress->attempts_count >= (int) config('netlearn.free_hint_after')
                && ! $puzzleProgress->is_completed;
            $missionStars = $puzzle->mission
                ? (int) UserMissionProgress::where('user_id', $user->user_id)->where('mission_id', $puzzle->mission_id)->value('best_stars')
                : 0;

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
                // The hint is free once a student has failed a few times (or after paying for it)
                'hint'                  => $isCorrect ? null : ($hintFree || (int) $puzzleProgress->hints_used > 0 ? $evaluation['hint'] : null),
                'hint_free'             => $hintFree,
                'diagnostics'           => $evaluation['diagnostics'] ?? null,
                'consequence'           => $evaluation['consequence'] ?? null,
                'attempts_count'        => (int) $puzzleProgress->attempts_count,
                'max_attempts'          => (int) $puzzle->max_attempts,
                'hints_used'            => (int) $puzzleProgress->hints_used,
                'try_attempts'          => (int) $puzzleProgress->try_attempts,
                'try_hint_used'         => (bool) $puzzleProgress->try_hint_used,
                'stars'                 => $stars,
                'best_stars'            => (int) $puzzleProgress->best_stars,
                'mission_stars'         => $missionStars,
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

        // A mission is cleared when every required puzzle is solved. The average best score is
        // kept only as an analytics number.
        $puzzleProgress = UserPuzzleProgress::where('user_id', $user->user_id)
            ->whereIn('puzzle_id', $requiredPuzzles->pluck('puzzle_id'))
            ->get();
        $totalMaxPoints = $requiredPuzzles->sum('points');
        $aggregatePercentage = $totalMaxPoints > 0
            ? (int) min(100, round(($puzzleProgress->sum('best_score') / $totalMaxPoints) * 100))
            : 0;
        if ($aggregatePercentage > $missionProgress->best_score_percentage) {
            $missionProgress->best_score_percentage = $aggregatePercentage;
        }

        $hasPassed = $puzzleProgress->where('is_completed', true)->count() === $requiredPuzzles->count();
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
            $nextMission = Mission::playable()
                ->where('topic_id', $mission->topic_id)
                ->where('mission_order', '>', $mission->mission_order)
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

        // Mission stars = rounded average of the counted activities' best stars (kept at its best)
        if ($missionProgress->is_completed) {
            $starValues = UserPuzzleProgress::where('user_id', $user->user_id)
                ->whereIn('puzzle_id', $requiredPuzzles->pluck('puzzle_id'))
                ->pluck('best_stars');
            $average = $requiredPuzzles->count() > 0 ? $starValues->sum() / $requiredPuzzles->count() : 0;
            $missionProgress->best_stars = max((int) $missionProgress->best_stars, (int) max(1, min(3, round($average))));
        }

        $missionProgress->save();

        return [
            'is_completed'          => $missionProgress->is_completed,
            'is_newly_completed'    => $isNewlyCompleted,
            'xp_awarded'            => $xpAwarded,
            'unlocked_next_mission' => $unlockedNext,
        ];
    }
}
