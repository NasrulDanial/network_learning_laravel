<?php

namespace App\Services;

use App\Models\Progress;
use App\Models\Achievement;
use App\Models\UserAchievement;

class AchievementService
{
    public static function checkAndAward($userId)
    {
        $progress = Progress::where('user_id', $userId)->first();
        if (!$progress) return;

        $achievements = Achievement::all();
        $newBadges = [];

        foreach ($achievements as $achievement) {
            // Check if user already has it
            $hasBadge = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->achievement_id)
                ->exists();

            if (!$hasBadge) {
                $earned = false;
                
                // Evaluate based on requirement type
                if ($achievement->requirement_type === 'topics_completed' && $progress->completed_missions >= $achievement->requirement_value) {
                    $earned = true;
                } elseif ($achievement->requirement_type === 'xp_earned' && $progress->total_xp >= $achievement->requirement_value) {
                    $earned = true;
                } // Add more conditions like 'perfect_score' here

                if ($earned) {
                    UserAchievement::create([
                        'user_id' => $userId,
                        'achievement_id' => $achievement->achievement_id,
                        'date_earned' => now()
                    ]);
                    $newBadges[] = $achievement->badge_name;
                }
            }
        }
        return $newBadges; // Can be returned to the frontend for a popup alert
    }
}