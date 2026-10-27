<?php

namespace App\Services;

use App\Models\Progress;
use App\Models\Achievement;
use App\Models\UserAchievement;

class AchievementService
{
    public static function checkAndAward($userId)
    {
        // Unified authoritative calculations across both PBL missions and conventional quizzes
        $pblXp = (int) Progress::where('user_id', $userId)->sum('total_xp');
        $quizXp = (int) \App\Models\Result::where('user_id', $userId)->sum('points_earned');
        $totalXp = $pblXp + $quizXp;

        $completedTopics = Progress::where('user_id', $userId)
            ->where('learning_status', 'Completed')
            ->count();

        $completedMissions = \App\Models\UserMissionProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $achievements = Achievement::all();
        $newBadges = [];

        foreach ($achievements as $achievement) {
            $hasBadge = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->achievement_id)
                ->exists();

            if (!$hasBadge) {
                $earned = false;

                if ($achievement->requirement_type === 'topics_completed' && $completedTopics >= $achievement->requirement_value) {
                    $earned = true;
                } elseif ($achievement->requirement_type === 'missions_completed' && $completedMissions >= $achievement->requirement_value) {
                    $earned = true;
                } elseif ($achievement->requirement_type === 'xp_earned' && $totalXp >= $achievement->requirement_value) {
                    $earned = true;
                }

                if ($earned) {
                    UserAchievement::firstOrCreate(
                        ['user_id' => $userId, 'achievement_id' => $achievement->achievement_id],
                        ['date_earned' => now()]
                    );
                    $newBadges[] = $achievement->badge_name;
                }
            }
        }

        return $newBadges;
    }
}