<?php

namespace App\Services;

/**
 * Mission health: one 0-100 bar for a whole mission run, split into three segments that are the
 * mission's three stars. It is modelled on the damage systems of games.
 *
 *  - One pool per run. Every task of the mission draws on the same bar, and it carries over
 *    from task to task until the mission ends. Restarting the mission refills it.
 *  - A wrong answer does damage scaled by how wrong it was (a near miss costs about a third
 *    of a total miss) and by the misses before it on the same task (x1, x1.15, x1.3, x1.45).
 *  - The bar never reaches zero: it bottoms out in a "safe mode" floor so retries stay
 *    unlimited, like games that leave you on 1 HP instead of ending the run.
 *  - Stars are the segments with health left when the last task is solved:
 *    67-100 = 3 stars, 34-66 = 2 stars, below that 1 star.
 *  - Hints cost XP, not health, and attempt limits are enforced separately.
 *
 * Health only reflects what happened; it never decides correctness or XP.
 */
class LinkHealthService
{
    public const MAX = 100;
    public const BASE_DAMAGE = 24;
    public const SAFE_FLOOR = 10;
    public const THREE_STARS_AT = 67;
    public const TWO_STARS_AT = 34;
    private const ESCALATION_STEP = 0.15;
    private const ESCALATION_CAP = 1.45;
    private const MIN_SEVERITY_SHARE = 0.35;

    /** How wrong the answer was: 1 = nothing right, towards 0 = almost everything right. */
    public static function severity(?array $diagnostics): float
    {
        $total = max(1, (int) ($diagnostics['total'] ?? 1));
        $correct = max(0, min($total, (int) ($diagnostics['correct_count'] ?? 0)));

        return 1 - $correct / $total;
    }

    /** Multiplier for the n-th miss in a row on one task (the first miss is x1). */
    public static function escalation(int $consecutiveMisses): float
    {
        return min(self::ESCALATION_CAP, 1 + self::ESCALATION_STEP * max(0, $consecutiveMisses - 1));
    }

    public static function damage(?array $diagnostics, int $consecutiveMisses): int
    {
        $share = self::MIN_SEVERITY_SHARE + (1 - self::MIN_SEVERITY_SHARE) * self::severity($diagnostics);

        return max(1, (int) round(self::BASE_DAMAGE * $share * self::escalation($consecutiveMisses)));
    }

    /** Stars for a health value: the segments that still hold health (never below 1 on a finished run). */
    public static function stars(int $health): int
    {
        return $health >= self::THREE_STARS_AT ? 3 : ($health >= self::TWO_STARS_AT ? 2 : 1);
    }

    public static function inSafeMode(int $health): bool
    {
        return $health <= self::SAFE_FLOOR;
    }

    /** Health after a wrong answer, with the numbers the panel animates. */
    public static function afterMiss(int $health, ?array $diagnostics, int $consecutiveMisses): array
    {
        $after = max(self::SAFE_FLOOR, $health - self::damage($diagnostics, $consecutiveMisses));

        return [
            'link_health' => $after,
            'damage' => max(0, $health - $after),
            'damage_multiplier' => round(self::escalation($consecutiveMisses), 2),
            'safe_mode' => self::inSafeMode($after),
            'health_stars' => self::stars($after),
        ];
    }
}
