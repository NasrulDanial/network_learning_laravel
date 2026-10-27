<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

/**
 * Badge definitions. Each description states the exact rule AchievementService checks
 * (requirement_type + requirement_value). Badges are not editable in the app, so these
 * definitions are owned by the seeder and kept in sync by badge name.
 * xp_reward is 0: badges celebrate milestones and do not grant extra XP.
 */
class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // Existing badges (names kept so earned records stay attached)
            ['Packet Tracer Initiate', 'Complete your first mission.', 'missions_completed', 1, 'fa-network-wired'],
            ['OSI Cartographer', 'Earn 250 XP from puzzles, missions and quizzes.', 'xp_earned', 250, 'fa-layer-group'],
            ['Clearance Level 2: Packet Analyst', 'Earn 500 XP to reach the Packet Analyst rank.', 'xp_earned', 500, 'fa-shield-halved'],
            ['Clearance Level 5: Principal Network Architect', 'Earn 2,000 XP to reach the top rank.', 'xp_earned', 2000, 'fa-award'],

            // Curriculum milestones
            ['Topic Finisher', 'Complete your first topic (pass its quiz, or finish all its missions if it has no quiz).', 'topics_completed', 1, 'fa-flag-checkered'],
            ['Mission Specialist', 'Complete 5 missions.', 'missions_completed', 5, 'fa-puzzle-piece'],
            ['Halfway Through the Stack', 'Complete 3 topics.', 'topics_completed', 3, 'fa-road'],
            ['XP Collector', 'Earn 1,000 XP.', 'xp_earned', 1000, 'fa-bolt'],
            ['Mission Master', 'Complete 15 missions.', 'missions_completed', 15, 'fa-medal'],
            ['Network Fundamentals Graduate', 'Complete 5 topics.', 'topics_completed', 5, 'fa-graduation-cap'],
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
