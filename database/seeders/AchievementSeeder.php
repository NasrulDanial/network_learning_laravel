<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

/**
 * Badge definitions (20). Each description states the exact rule AchievementService checks
 * (requirement_type + requirement_value). Badges are not editable in the app, so these
 * definitions are owned by the seeder and kept in sync by badge name.
 * xp_reward is 0: badges celebrate milestones and do not grant extra XP.
 *
 * Names are game-style. Six older, plainer names are renamed in place (same row), so badges
 * students already earned stay earned under their new name.
 */
class AchievementSeeder extends Seeder
{
    /** old name => new name */
    private const RENAMES = [
        'Packet Tracer Initiate' => 'First Ping!',
        'Topic Finisher' => 'Stage Clear!',
        'Mission Specialist' => 'Side Quest Slayer',
        'XP Collector' => 'Loot Goblin',
        'Mission Master' => 'Boss Rush Champion',
        'Network Fundamentals Graduate' => 'Fundamentals Unlocked',
    ];

    public function run(): void
    {
        foreach (self::RENAMES as $old => $new) {
            $existing = Achievement::where('badge_name', $old)->first();
            if ($existing && ! Achievement::where('badge_name', $new)->exists()) {
                $existing->update(['badge_name' => $new]);
            }
        }

        $badges = [
            // Getting started
            ['First Ping!', 'Complete your first mission. The network answered!', 'missions_completed', 1, 'fa-network-wired'],
            ['Stage Clear!', 'Complete your first stage (pass its quiz, or finish all its missions if it has no quiz).', 'topics_completed', 1, 'fa-flag-checkered'],
            ['OSI Cartographer', 'Earn 250 XP from puzzles, missions and quizzes.', 'xp_earned', 250, 'fa-layer-group'],

            // Missions and stages
            ['Side Quest Slayer', 'Complete 5 missions.', 'missions_completed', 5, 'fa-puzzle-piece'],
            ['Halfway Through the Stack', 'Complete 3 stages.', 'topics_completed', 3, 'fa-road'],
            ['Boss Rush Champion', 'Complete 15 missions.', 'missions_completed', 15, 'fa-medal'],
            ['Fundamentals Unlocked', 'Complete 5 stages.', 'topics_completed', 5, 'fa-graduation-cap'],
            ['Network Legend', 'Complete all 10 stages. The final boss is down.', 'topics_completed', 10, 'fa-crown'],

            // XP and clearance rank
            ['Clearance Level 2: Packet Analyst', 'Earn 500 XP to reach the Packet Analyst rank.', 'xp_earned', 500, 'fa-shield-halved'],
            ['Loot Goblin', 'Earn 1,000 XP.', 'xp_earned', 1000, 'fa-bolt'],
            ['Clearance Level 5: Principal Network Architect', 'Earn 2,000 XP to reach the top rank.', 'xp_earned', 2000, 'fa-award'],
            ['Hoard of Bytes', 'Earn 5,000 XP. The dragon would be jealous.', 'xp_earned', 5000, 'fa-dragon'],

            // Quizzes
            ['Quiz Whiz', 'Pass the assessment quiz of 3 different stages.', 'quizzes_passed', 3, 'fa-brain'],
            ['Perfect Handshake', 'Answer every question of an assessment correctly (100%).', 'perfect_quizzes', 1, 'fa-handshake'],
            ['Flawless Victory', 'Score 100% on the assessments of 3 different stages.', 'perfect_quizzes', 3, 'fa-trophy'],

            // Stars and puzzles
            ['Triple Star', 'Finish a mission with a 3-star rating.', 'three_star_missions', 1, 'fa-star'],
            ['Star Collector', 'Finish 5 missions with a 3-star rating.', 'three_star_missions', 5, 'fa-wand-magic-sparkles'],
            ['Puzzle Hoarder', 'Solve 25 puzzle activities.', 'puzzles_solved', 25, 'fa-cubes'],
            ['Big Brain Energy', 'Solve 75 puzzle activities.', 'puzzles_solved', 75, 'fa-lightbulb'],
            ['Clean Run', 'Earn 3 stars on 10 puzzles: first try, no hints.', 'three_star_puzzles', 10, 'fa-bolt-lightning'],
        ];

        foreach ($badges as [$name, $description, $type, $value, $icon]) {
            Achievement::updateOrCreate(
                ['badge_name' => $name],
                [
                    'description' => $description,
                    'requirement_type' => $type,
                    'requirement_value' => $value,
                    'badge_icon' => $icon,
                    'xp_reward' => 0,
                ]
            );
        }

        if ($this->command) {
            $this->command->info('   Achievements: ' . count($badges) . ' badge definitions synced');
        }
    }
}
