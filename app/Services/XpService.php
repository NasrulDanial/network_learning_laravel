<?php

namespace App\Services;

use App\Models\Progress;
use App\Models\Result;

/** The one definition of a student's total XP: mission/puzzle XP per topic plus quiz XP. */
class XpService
{
    public static function total(int $userId): int
    {
        return (int) Progress::where('user_id', $userId)->sum('total_xp')
            + (int) Result::where('user_id', $userId)->sum('points_earned');
    }

    public static function rank(int $userId): array
    {
        return RankProgressionService::getRankTelemetry(self::total($userId));
    }
}
