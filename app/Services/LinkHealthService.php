<?php

namespace App\Services;

/**
 * Link health: the 0-100 bar beside a puzzle. It is a feedback meter, modelled on the
 * damage systems of games rather than on the star rating (which has its own rule).
 *
 *  - Damage scales with how wrong the answer was ("chip" damage for a near miss, full
 *    damage for nothing right), using the diagnostics the evaluator already returns.
 *  - Damage escalates with consecutive misses in the same try (x1, x1.15, x1.3, x1.45 max),
 *    like the combo and pressure systems that punish button-mashing.
 *  - Unlimited tasks never fail: health bottoms out at a "safe mode" floor, the way many
 *    games leave you at 1 HP instead of ending the run.
 *  - Tasks with an attempt limit show attempts left as health, because the server really
 *    locks them at zero.
 *  - A solve ends the try and refills health to full; a Mission Run restart does the same.
 *
 * Health never decides correctness, XP or stars; it is derived from the same facts.
 */
class LinkHealthService
{
    public const MAX = 100;
    public const BASE_DAMAGE = 24;
    public const SAFE_FLOOR = 10;
    private const ESCALATION_STEP = 0.15;
    private const ESCALATION_CAP = 1.45;
    private const MIN_SEVERITY_SHARE = 0.35; // even a near miss costs a third of full damage

    /** How wrong the answer was: 1 = nothing right, towards 0 = almost everything right. */
    public static function severity(?array $diagnostics): float
    {
        $total = max(1, (int) ($diagnostics['total'] ?? 1));
        $correct = max(0, min($total, (int) ($diagnostics['correct_count'] ?? 0)));

        return 1 - $correct / $total;
    }

    /** Multiplier for the n-th miss in a row within one try (the first miss is x1). */
    public static function escalation(int $consecutiveMisses): float
    {
        return min(self::ESCALATION_CAP, 1 + self::ESCALATION_STEP * max(0, $consecutiveMisses - 1));
    }

    public static function damage(?array $diagnostics, int $consecutiveMisses): int
    {
        $share = self::MIN_SEVERITY_SHARE + (1 - self::MIN_SEVERITY_SHARE) * self::severity($diagnostics);

        return max(1, (int) round(self::BASE_DAMAGE * $share * self::escalation($consecutiveMisses)));
    }

    /**
     * Health after a wrong answer.
     *
     * @param int $health          health before the answer
     * @param int $maxAttempts     the task's attempt limit (0 = unlimited)
     * @param int $attemptsUsed    attempts used including this one (limited tasks only)
     */
    public static function afterMiss(int $health, ?array $diagnostics, int $consecutiveMisses, int $maxAttempts, int $attemptsUsed): array
    {
        $damage = self::damage($diagnostics, $consecutiveMisses);

        if ($maxAttempts > 0) {
            $after = (int) round(self::MAX * max(0, $maxAttempts - $attemptsUsed) / $maxAttempts);
            $safeMode = false;
        } else {
            $after = max(self::SAFE_FLOOR, $health - $damage);
            $safeMode = $after <= self::SAFE_FLOOR;
        }

        return [
            'link_health' => $after,
            'damage' => max(0, $health - $after),
            'damage_multiplier' => round(self::escalation($consecutiveMisses), 2),
            'safe_mode' => $safeMode,
        ];
    }
}
