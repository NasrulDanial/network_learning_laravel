<?php

namespace App\Services;

use App\Models\Progress;
use App\Models\Achievement;
use App\Models\UserAchievement;

class AchievementService
{
    public static function checkAndAward($userId)
    {
        // Aggregate user's overall progress across all topics
        $totalXp = Progress::where('user_id', $userId)->sum('total_xp');
        $completedMissions = Progress::where('user_id', $userId)->sum('completed_missions');

        $achievements = Achievement::all();
        $newBadges = [];

        foreach ($achievements as $achievement) {
            $hasBadge = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->achievement_id)
                ->exists();

            if (!$hasBadge) {
                $earned = false;

                if ($achievement->requirement_type === 'topics_completed' && $completedMissions >= $achievement->requirement_value) {
                    $earned = true;
                } elseif ($achievement->requirement_type === 'xp_earned' && $totalXp >= $achievement->requirement_value) {
                    $earned = true;
                }

                if ($earned) {
                    UserAchievement::create([
                        'user_id' => $userId,
                        'achievement_id' => $achievement->achievement_id,
                        'date_earned' => now(),
                    ]);
                    $newBadges[] = $achievement->badge_name;
                }
            }
        }

        return $newBadges;
    }
}