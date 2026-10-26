<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Topic;
use App\Models\Puzzle;
use App\Models\Achievement;
use App\Models\User;

class NetworkingMissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'lecturer')->first() ?? User::first();
        $adminId = $admin ? $admin->user_id : 1;

        // 1. Networking Curriculum Topics
        $osiTopic = Topic::updateOrCreate(
            ['topic_order' => 1],
            [
                'title' => 'OSI Reference Model & PDU Encapsulation',
                'description' => 'Analyze the 7 architectural abstraction layers, data unit encapsulation, and transmission protocols.',
                'created_by' => $adminId,
            ]
        );

        $hardwareTopic = Topic::updateOrCreate(
            ['topic_order' => 2],
            [
                'title' => 'Network Infrastructure & Broadcast Domains',
                'description' => 'Distinguish Layer 1 physical signals, Layer 2 MAC switching fabrics, and Layer 3 IP routing gateways.',
                'created_by' => $adminId,
            ]
        );

        // 2. Interactive Networking Missions (Phaser Mini-Games)
        Puzzle::updateOrCreate(
            ['puzzle_id' => 1],
            [
                'topic_id' => $hardwareTopic->topic_id,
                'puzzle_type' => 'drag_and_drop',
                'question_text' => 'Segment the network infrastructure across correct OSI architectural layers.',
                'instruction' => 'Hardware Topology Mapping: Deploy Routers, Managed Switches, and Physical Hubs to their operating OSI layers.',
                'correct_answer' => [
                    'router' => 'Layer 3 (IP Routing)',
                    'switch' => 'Layer 2 (MAC Switching)',
                    'hub'    => 'Layer 1 (Electrical Signals)'
                ],
                'hint' => 'Routers forward IP packets across subnets; Switches forward frames via hardware MAC tables; Hubs repeat raw electrical bits.',
                'explanation' => 'Routers operate at Layer 3 (Network Layer) evaluating logical IP headers; Switches operate at Layer 2 (Data Link) managing MAC addresses and collision domains; Hubs are dumb Layer 1 (Physical) repeaters sharing a single collision domain.',
                'points' => 150, // Network XP
            ]
        );

        Puzzle::updateOrCreate(
            ['puzzle_id' => 2],
            [
                'topic_id' => $osiTopic->topic_id,
                'puzzle_type' => 'sequencing',
                'question_text' => 'Construct the Protocol Data Unit (PDU) encapsulation sequence for egress data transmission.',
                'instruction' => 'OSI Encapsulation Mission: Sequence the 7 layers from high-level Application data down to Physical bitstream.',
                'correct_answer' => [
                    'Application',
                    'Presentation',
                    'Session',
                    'Transport',
                    'Network',
                    'Data Link',
                    'Physical'
                ],
                'hint' => 'Telemetry mnemonic: [All People Seem To Need Data Processing]. Start from user-level software down to the transmission medium.',
                'explanation' => 'Encapsulation descends: Layer 7 (Application Data) -> Layer 6 (Presentation Syntax/Encryption) -> Layer 5 (Session Management) -> Layer 4 (Transport Segments) -> Layer 3 (Network IP Packets) -> Layer 2 (Data Link Frames) -> Layer 1 (Physical Bits).',
                'points' => 200, // Network XP
            ]
        );

        // 3. Network Clearance Badges
        $badges = [
            [
                'badge_name' => 'Packet Tracer Initiate',
                'description' => 'Successfully verified your first network infrastructure topology mapping.',
                'xp_reward' => 100,
                'badge_icon' => 'fa-network-wired',
                'requirement_type' => 'topics_completed',
                'requirement_value' => 1,
            ],
            [
                'badge_name' => 'Clearance Level 2: Packet Analyst',
                'description' => 'Accumulated 500+ Network XP through verified networking laboratory simulations.',
                'xp_reward' => 250,
                'badge_icon' => 'fa-shield-halved',
                'requirement_type' => 'xp_earned',
                'requirement_value' => 500,
            ],
            [
                'badge_name' => 'Clearance Level 5: Principal Network Architect',
                'description' => 'Mastered all simulation missions and achieved top security clearance.',
                'xp_reward' => 500,
                'badge_icon' => 'fa-award',
                'requirement_type' => 'xp_earned',
                'requirement_value' => 2000,
            ],
        ];

        foreach ($badges as $badge) {
            Achievement::updateOrCreate(
                ['badge_name' => $badge['badge_name']],
                $badge
            );
        }
    }
}
