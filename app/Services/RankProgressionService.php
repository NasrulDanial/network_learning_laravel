<?php

namespace App\Services;

class RankProgressionService
{
    public const TIER_SIZE = 500;

    public const RANKS = [
        1 => [
            'level' => 1,
            'title' => 'Clearance Level 1: Network Cadet',
            'short_title' => 'Network Cadet',
            'min_xp' => 0,
            'max_xp' => 499,
            'description' => 'Beginning operative mastering OSI fundamentals, cable types, and basic host configurations.',
            'icon' => 'fa-user-astronaut',
        ],
        2 => [
            'level' => 2,
            'title' => 'Clearance Level 2: Packet Analyst',
            'short_title' => 'Packet Analyst',
            'min_xp' => 500,
            'max_xp' => 999,
            'description' => 'Analyzes PDU encapsulation, Wireshark headers, and Ethernet frame structures.',
            'icon' => 'fa-magnifying-glass-chart',
        ],
        3 => [
            'level' => 3,
            'title' => 'Clearance Level 3: Infrastructure Technician',
            'short_title' => 'Infrastructure Technician',
            'min_xp' => 1000,
            'max_xp' => 1499,
            'description' => 'Configures VLANs, trunk links, IPv4 CIDR subnetting, and gateway interfaces.',
            'icon' => 'fa-server',
        ],
        4 => [
            'level' => 4,
            'title' => 'Clearance Level 4: Network Security Specialist',
            'short_title' => 'Security Specialist',
            'min_xp' => 1500,
            'max_xp' => 1999,
            'description' => 'Hardens routers, configures standard/extended ACLs, and verifies NAT translation.',
            'icon' => 'fa-shield-halved',
        ],
        5 => [
            'level' => 5,
            'title' => 'Clearance Level 5: Principal Network Architect',
            'short_title' => 'Principal Architect',
            'min_xp' => 2000,
            'max_xp' => null,
            'description' => 'Designs high-availability enterprise topologies, OSPF convergence, and multi-area routing.',
            'icon' => 'fa-award',
        ],
    ];

    /**
     * Compute authoritative rank telemetry from accumulated XP.
     */
    public static function getRankTelemetry(int $totalXp): array
    {
        $totalXp = max(0, $totalXp);
        $level = (int) floor($totalXp / self::TIER_SIZE) + 1;
        $level = min($level, 5);

        $currentRank = self::RANKS[$level];
        $isMaxTier = $level >= 5;

        $tierBaseXp = ($level - 1) * self::TIER_SIZE;
        $nextTierXp = $level * self::TIER_SIZE;
        $tierProgressXp = $totalXp - $tierBaseXp;

        if ($isMaxTier) {
            $tierProgressPct = 100;
            $xpRemaining = 0;
        } else {
            $tierProgressPct = min(100, (int) round(($tierProgressXp / self::TIER_SIZE) * 100));
            $xpRemaining = max(0, $nextTierXp - $totalXp);
        }

        $allRanks = [];
        foreach (self::RANKS as $lvl => $r) {
            $allRanks[] = [
                'level' => $lvl,
                'title' => $r['title'],
                'short_title' => $r['short_title'],
                'min_xp' => $r['min_xp'],
                'max_xp' => $r['max_xp'],
                'description' => $r['description'],
                'icon' => $r['icon'],
                'is_unlocked' => $totalXp >= $r['min_xp'],
                'is_current' => $lvl === $level,
            ];
        }

        return [
            'current_level' => $level,
            'tier' => $level,
            'title' => $currentRank['short_title'],
            'tagline' => $currentRank['description'],
            'current_rank_title' => $currentRank['title'],
            'current_rank_short' => $currentRank['short_title'],
            'current_rank_desc' => $currentRank['description'],
            'current_rank_icon' => $currentRank['icon'],
            'total_xp' => $totalXp,
            'currentXp' => $totalXp,
            'tier_base_xp' => $tierBaseXp,
            'next_tier_xp' => $isMaxTier ? null : $nextTierXp,
            'nextTierXp' => $isMaxTier ? null : $nextTierXp,
            'tier_progress_xp' => $tierProgressXp,
            'tier_progress_pct' => $tierProgressPct,
            'tierProgressPct' => $tierProgressPct,
            'xp_remaining' => $xpRemaining,
            'is_max_tier' => $isMaxTier,
            'all_ranks' => $allRanks,
            'disclaimer' => 'Career ranks reflect platform progression and completed challenges within NetLearn, not a formal industrial certification.',
        ];
    }
}
