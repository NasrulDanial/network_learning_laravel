<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Progress;
use App\Models\Result;
use App\Models\UserAchievement;
use App\Models\UserMissionProgress;
use App\Models\UserPuzzleProgress;

class AchievementService
{
    /** Badge requirement types and how the achievements page labels their counter. */
    public const REQUIREMENT_LABELS = [
        'xp_earned' => 'XP Target',
        'topics_completed' => 'Stages Completed',
        'missions_completed' => 'Missions Cleared',
        'quizzes_passed' => 'Quizzes Passed',
        'perfect_quizzes' => 'Perfect Quizzes',
        'three_star_missions' => 'Missions at 3 Stars',
        'puzzles_solved' => 'Puzzles Solved',
        'three_star_puzzles' => 'Puzzles at 3 Stars',
    ];

    /** What the student has achieved so far, per requirement type. */
    public static function metrics(int $userId): array
    {
        $passed = Result::where('user_id', $userId)->where('status', 'Pass');

        return [
            'xp_earned' => XpService::total($userId),
            'topics_completed' => Progress::where('user_id', $userId)->where('learning_status', 'Completed')->count(),
            'missions_completed' => UserMissionProgress::where('user_id', $userId)->where('is_completed', true)->count(),
            'quizzes_passed' => (clone $passed)->distinct()->count('quiz_id'),
            'perfect_quizzes' => (clone $passed)->where('percentage', '>=', 100)->distinct()->count('quiz_id'),
            'three_star_missions' => UserMissionProgress::where('user_id', $userId)->where('best_stars', 3)->count(),
            'puzzles_solved' => UserPuzzleProgress::where('user_id', $userId)->where('is_completed', true)->count(),
            'three_star_puzzles' => UserPuzzleProgress::where('user_id', $userId)->where('best_stars', 3)->count(),
        ];
    }

    public static function checkAndAward($userId)
    {
        $metrics = self::metrics((int) $userId);
        $earnedIds = UserAchievement::where('user_id', $userId)->pluck('achievement_id')->all();
        $newBadges = [];

        foreach (Achievement::all() as $achievement) {
            if (in_array($achievement->achievement_id, $earnedIds, true)) {
                continue;
            }

            $current = $metrics[$achievement->requirement_type] ?? null;
            if ($current === null || $current < $achievement->requirement_value) {
                continue;
            }

            UserAchievement::firstOrCreate(
                ['user_id' => $userId, 'achievement_id' => $achievement->achievement_id],
                ['date_earned' => now()]
            );
            $newBadges[] = $achievement->badge_name;
        }

        return $newBadges;
    }
}
