<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Progress;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Topic;
use App\Models\UserMissionProgress;

/**
 * Topic progress is derived from facts (missions cleared, quiz passed) instead of being
 * nudged up by events, so every page shows the same number.
 *
 *   completed = all missions cleared AND (quiz passed OR the topic has no quiz) -> 100%
 *   otherwise 10% for opening the topic + up to 70% for missions (10..80)
 *
 * total_xp is never touched here: XP stays event-based.
 */
class TopicProgressService
{
    public function recalculate(int $userId, Topic $topic): Progress
    {
        $progress = Progress::firstOrNew(['user_id' => $userId, 'topic_id' => $topic->topic_id]);
        if (! $progress->exists) {
            $progress->total_xp = 0;
        }

        $missionIds = Mission::playable()->where('topic_id', $topic->topic_id)->pluck('mission_id');
        $totalMissions = $missionIds->count();
        $doneMissions = $totalMissions === 0 ? 0 : UserMissionProgress::where('user_id', $userId)
            ->whereIn('mission_id', $missionIds)
            ->where('is_completed', true)
            ->count();

        $quiz = Quiz::where('topic_id', $topic->topic_id)->whereHas('questions')->orderBy('quiz_id')->first();
        $passed = $quiz && Result::where('user_id', $userId)->where('quiz_id', $quiz->quiz_id)->where('status', 'Pass')->exists();
        $attempted = $quiz && Result::where('user_id', $userId)->where('quiz_id', $quiz->quiz_id)->exists();

        $missionsDone = $doneMissions === $totalMissions; // a topic without missions counts as done
        $completed = $missionsDone && ($passed || ! $quiz);

        // Never downgrade a completed topic (e.g. a lecturer adds a mission later), so
        // students are never locked out of topics they already unlocked.
        if ($progress->exists && $progress->learning_status === 'Completed') {
            $completed = true;
        }

        if ($completed) {
            $pct = 100;
        } elseif ($totalMissions > 0) {
            $pct = 10 + (int) round(70 * $doneMissions / $totalMissions);
        } else {
            $pct = 10;
        }

        $progress->completed_missions = $doneMissions;
        $progress->progress_percentage = $pct;
        $progress->learning_status = $completed ? 'Completed' : 'In Progress';
        $progress->quiz_status = $passed ? 'Passed' : ($attempted ? 'Failed' : 'Not Attempted');
        $progress->save();

        return $progress;
    }
}
