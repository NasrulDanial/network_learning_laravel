<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Topic;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Achievement;
use App\Models\User;

/**
 * NetworkingMissionSeeder
 *
 * Seeds one complete example of all 6 PBL puzzle archetypes across two missions
 * attached to the first two curriculum topics.
 *
 * Safe to run multiple times — uses updateOrCreate.
 * ZERO destructive operations — existing user data is preserved.
 */
class NetworkingMissionSeeder extends Seeder
{
    public function run(): void
    {
        $lecturer = User::where('role', 'lecturer')->first() ?? User::first();
        $lecturerId = $lecturer?->user_id ?? 1;

        // ── Topic 1: OSI Reference Model ────────────────────────────────────
        $osiTopic = Topic::updateOrCreate(
            ['topic_order' => 1],
            [
                'title'       => 'OSI Reference Model & PDU Encapsulation',
                'description' => 'Analyze the 7 architectural abstraction layers, data unit encapsulation, and protocol data flows.',
                'content'     => "The OSI (Open Systems Interconnection) model is a conceptual framework that standardizes the functions of a telecommunication system into seven distinct layers.\n\nEach layer serves the layer above it and is served by the layer below. Understanding these layers is fundamental to diagnosing network issues, designing infrastructure, and selecting correct protocols.",
                'created_by'  => $lecturerId,
            ]
        );

        // ── Topic 2: Network Infrastructure & Broadcast Domains ────────────
        $hwTopic = Topic::updateOrCreate(
            ['topic_order' => 2],
            [
                'title'       => 'Network Infrastructure & Broadcast Domains',
                'description' => 'Distinguish Layer 1 physical signals, Layer 2 MAC switching fabrics, and Layer 3 IP routing gateways.',
                'content'     => "Network infrastructure devices operate at specific OSI layers, determining how traffic is forwarded, filtered, and segmented.\n\n• Hubs (Layer 1) repeat all incoming signals — they share one collision domain.\n• Switches (Layer 2) forward frames based on MAC addresses, creating separate collision domains per port.\n• Routers (Layer 3) route packets between IP subnets, separating broadcast domains.",
                'created_by'  => $lecturerId,
            ]
        );

        // ════════════════════════════════════════════════════════════════════
        // MISSION 1 — OSI Topic — contains Archetypes: Identification,
        //             Matching, Sequencing
        // ════════════════════════════════════════════════════════════════════
        $mission1 = Mission::updateOrCreate(
            ['topic_id' => $osiTopic->topic_id, 'mission_order' => 1],
            [
                'title'       => 'Mission 1: OSI Layer Fundamentals',
                'description' => 'Core identification and sequencing challenges for the OSI 7-layer model.',
                'objective'   => 'A critical routing failure has disrupted enterprise comms. Your mission: correctly identify OSI layer roles, match protocols to their layers, and reconstruct the encapsulation sequence to restore connectivity.',
                'difficulty'  => 'beginner',
                'pass_mark'   => 70,
                'xp_reward'   => 200,
                'status'      => 'published',
            ]
        );

        // ── Activity 1A: Identification ──────────────────────────────────────
        Puzzle::updateOrCreate(
            ['mission_id' => $mission1->mission_id, 'activity_order' => 1],
            [
                'topic_id'           => $osiTopic->topic_id,
                'puzzle_type'        => 'identification',
                'question_text'      => 'Layer Identification: Which OSI layer is responsible for end-to-end error detection and flow control between hosts?',
                'instruction'        => 'Review the OSI layer responsibilities and select the single layer that manages reliable delivery, segmentation, and port-based multiplexing.',
                'activity_data'      => [
                    'prompt'  => 'Which OSI layer handles end-to-end error detection, flow control, and reliable delivery?',
                    'options' => [
                        ['id' => 'opt_1', 'label' => 'Layer 2 — Data Link (Framing & MAC Addressing)'],
                        ['id' => 'opt_2', 'label' => 'Layer 3 — Network (IP Routing & Logical Addressing)'],
                        ['id' => 'opt_3', 'label' => 'Layer 4 — Transport (TCP/UDP & Port Multiplexing)'],
                        ['id' => 'opt_4', 'label' => 'Layer 5 — Session (Connection Setup & Teardown)'],
                    ],
                ],
                'correct_answer'     => ['correct_option' => 'opt_3'],
                'hint'               => 'Think about TCP — which layer owns segments and port numbers?',
                'correct_feedback'   => 'Correct! Layer 4 (Transport) handles TCP/UDP, flow control, and port-based multiplexing.',
                'incorrect_feedback' => 'Review Layer 4. TCP and UDP operate here — managing reliable delivery and segmentation.',
                'points'             => 100,
                'difficulty'         => 'beginner',
                'is_required'        => true,
                'status'             => 'published',
            ]
        );

        // ── Activity 1B: Matching ────────────────────────────────────────────
        Puzzle::updateOrCreate(
            ['mission_id' => $mission1->mission_id, 'activity_order' => 2],
            [
                'topic_id'           => $osiTopic->topic_id,
                'puzzle_type'        => 'matching',
                'question_text'      => 'Protocol-to-Layer Mapping: Match each networking protocol to its primary OSI layer.',
                'instruction'        => 'Draw connection lines from each protocol on the left to its correct OSI layer on the right. All protocols must be matched before you can submit.',
                'activity_data'      => [
                    'left_items'  => [
                        ['id' => 'L_1', 'label' => 'HTTP / HTTPS'],
                        ['id' => 'L_2', 'label' => 'TCP / UDP'],
                        ['id' => 'L_3', 'label' => 'IP (IPv4 / IPv6)'],
                        ['id' => 'L_4', 'label' => 'Ethernet (IEEE 802.3)'],
                    ],
                    'right_items' => [
                        ['id' => 'R_1', 'label' => 'Layer 7 — Application'],
                        ['id' => 'R_2', 'label' => 'Layer 4 — Transport'],
                        ['id' => 'R_3', 'label' => 'Layer 3 — Network'],
                        ['id' => 'R_4', 'label' => 'Layer 2 — Data Link'],
                    ],
                ],
                'correct_answer'     => [
                    'pairs' => [
                        'L_1' => 'R_1',
                        'L_2' => 'R_2',
                        'L_3' => 'R_3',
                        'L_4' => 'R_4',
                    ],
                ],
                'hint'               => 'HTTP is web application traffic; Ethernet is the physical LAN framing protocol.',
                'correct_feedback'   => 'Perfect mapping! All protocols correctly assigned to their OSI layers.',
                'incorrect_feedback' => 'Review the OSI model. HTTP operates at Layer 7, TCP/UDP at Layer 4, IP at Layer 3, Ethernet at Layer 2.',
                'points'             => 150,
                'difficulty'         => 'beginner',
                'is_required'        => true,
                'status'             => 'published',
            ]
        );

        // ── Activity 1C: Sequencing ──────────────────────────────────────────
        Puzzle::updateOrCreate(
            ['mission_id' => $mission1->mission_id, 'activity_order' => 3],
            [
                'topic_id'           => $osiTopic->topic_id,
                'puzzle_type'        => 'sequencing',
                'question_text'      => 'TCP Three-Way Handshake: Reconstruct the connection setup sequence in chronological order.',
                'instruction'        => 'Drag the scrambled handshake events from the right into the ordered timeline slots (Step 1 → Step 3) on the left to establish a valid TCP session.',
                'activity_data'      => [
                    'prompt' => 'Arrange the TCP 3-way handshake steps in correct chronological order:',
                    'items'  => [
                        ['id' => 'seq_1', 'label' => 'Client → Server: SYN (synchronize; proposes initial sequence number ISN-C)'],
                        ['id' => 'seq_2', 'label' => 'Server → Client: SYN-ACK (acknowledges ISN-C, proposes own ISN-S)'],
                        ['id' => 'seq_3', 'label' => 'Client → Server: ACK (acknowledges ISN-S; full-duplex session established)'],
                    ],
                ],
                'correct_answer'     => [
                    'order' => ['seq_1', 'seq_2', 'seq_3'],
                ],
                'hint'               => 'The client always initiates with SYN. Remember: SYN → SYN-ACK → ACK.',
                'correct_feedback'   => 'Correct handshake sequence! The TCP session is now established and ready for reliable data transfer.',
                'incorrect_feedback' => 'Not quite. The client must send the initial SYN first — the server cannot initiate a TCP three-way handshake.',
                'points'             => 125,
                'difficulty'         => 'beginner',
                'is_required'        => true,
                'status'             => 'published',
            ]
        );

        // ════════════════════════════════════════════════════════════════════
        // MISSION 2 — Hardware Topic — contains Archetypes: Classification,
        //             Drag & Drop (spatial), Scenario Decision
        // ════════════════════════════════════════════════════════════════════
        $mission2 = Mission::updateOrCreate(
            ['topic_id' => $hwTopic->topic_id, 'mission_order' => 1],
            [
                'title'       => 'Mission 1: Network Device Deployment',
                'description' => 'Hands-on classification and spatial placement challenges for networking hardware.',
                'objective'   => 'Datacenter Network Operations: The LAN infrastructure blueprint is unclassified. Deploy devices to correct broadcast domains, place hardware on the OSI topology canvas, and diagnose the connectivity fault to restore uptime.',
                'difficulty'  => 'intermediate',
                'pass_mark'   => 70,
                'xp_reward'   => 300,
                'status'      => 'published',
            ]
        );

        // ── Activity 2A: Classification ─────────────────────────────────────
        Puzzle::updateOrCreate(
            ['mission_id' => $mission2->mission_id, 'activity_order' => 1],
            [
                'topic_id'           => $hwTopic->topic_id,
                'puzzle_type'        => 'classification',
                'question_text'      => 'Device Classification: Sort each networking component into its correct OSI operational layer.',
                'instruction'        => 'Drag each device or protocol token from the staging area into the correct OSI layer bin. All items must be classified before you can submit.',
                'activity_data'      => [
                    'categories' => [
                        ['id' => 'cat_1', 'label' => 'Layer 1 — Physical (Bits & Signals)'],
                        ['id' => 'cat_2', 'label' => 'Layer 2 — Data Link (Frames & MACs)'],
                        ['id' => 'cat_3', 'label' => 'Layer 3 — Network (Packets & IPs)'],
                        ['id' => 'cat_4', 'label' => 'Layer 4 — Transport (Segments & Ports)'],
                        ['id' => 'cat_5', 'label' => 'Layer 7 — Application (Data & APIs)'],
                    ],
                    'items' => [
                        ['id' => 'item_1', 'label' => 'Network Hub / Repeater'],
                        ['id' => 'item_2', 'label' => 'RJ-45 Copper Cable'],
                        ['id' => 'item_3', 'label' => 'Managed Layer 2 Switch'],
                        ['id' => 'item_4', 'label' => 'ARP (Address Resolution Protocol)'],
                        ['id' => 'item_5', 'label' => 'Core Internet Router'],
                        ['id' => 'item_6', 'label' => 'OSPF Routing Protocol'],
                        ['id' => 'item_7', 'label' => 'TCP (Transmission Control Protocol)'],
                        ['id' => 'item_8', 'label' => 'HTTP Web Browser Request'],
                    ],
                ],
                'correct_answer'     => [
                    'mapping' => [
                        'item_1' => 'cat_1',  // Hub → Physical
                        'item_2' => 'cat_1',  // Cable → Physical
                        'item_3' => 'cat_2',  // L2 Switch → Data Link
                        'item_4' => 'cat_2',  // ARP → Data Link
                        'item_5' => 'cat_3',  // Router → Network
                        'item_6' => 'cat_3',  // OSPF → Network
                        'item_7' => 'cat_4',  // TCP → Transport
                        'item_8' => 'cat_5',  // HTTP → Application
                    ],
                ],
                'hint'               => 'ARP resolves MAC addresses — it operates at Data Link. OSPF advertises routing tables — it works with IP at the Network layer.',
                'correct_feedback'   => 'All devices correctly classified! Broadcast domain architecture fully mapped.',
                'incorrect_feedback' => 'Some items are misclassified. Review which physical media and hub types belong to Layer 1, vs switches (Layer 2) and routers (Layer 3).',
                'points'             => 200,
                'difficulty'         => 'intermediate',
                'is_required'        => true,
                'status'             => 'published',
            ]
        );

        // ── Activity 2B: Drag & Drop (Spatial Device Placement) ─────────────
        Puzzle::updateOrCreate(
            ['mission_id' => $mission2->mission_id, 'activity_order' => 2],
            [
                'topic_id'           => $hwTopic->topic_id,
                'puzzle_type'        => 'drag_and_drop',
                'question_text'      => 'Topology Deployment: Place each network device into its correct OSI architecture zone.',
                'instruction'        => 'Drag the hardware devices from the staging bay above into the matching architectural zones below. Ensure each device occupies its correct layer footprint.',
                'activity_data'      => [
                    'template_key' => 'device_to_osi_layer',
                    'draggables'   => [
                        ['id' => 'drag_1', 'label' => 'Core Router (Cisco ISR)'],
                        ['id' => 'drag_2', 'label' => 'Managed L2 Switch'],
                        ['id' => 'drag_3', 'label' => 'Passive Hub / Repeater'],
                    ],
                    'drop_zones'   => [
                        ['id' => 'zone_1', 'label' => 'Layer 3 — Network (IP Routing)'],
                        ['id' => 'zone_2', 'label' => 'Layer 2 — Data Link (MAC Switching)'],
                        ['id' => 'zone_3', 'label' => 'Layer 1 — Physical (Signal Repeating)'],
                    ],
                ],
                'correct_answer'     => [
                    'placements' => [
                        'drag_1' => 'zone_1',  // Router → L3
                        'drag_2' => 'zone_2',  // Switch → L2
                        'drag_3' => 'zone_3',  // Hub → L1
                    ],
                ],
                'hint'               => 'Routers inspect IP headers (Layer 3). Switches inspect MAC headers (Layer 2). Hubs repeat raw signals (Layer 1).',
                'correct_feedback'   => 'Topology deployed correctly! All devices are in their architectural zones.',
                'incorrect_feedback' => 'Some devices are misplaced. Remember: Routers route IP (L3), Switches forward MAC (L2), Hubs repeat signals (L1).',
                'points'             => 150,
                'difficulty'         => 'intermediate',
                'is_required'        => true,
                'status'             => 'published',
            ]
        );

        // ── Activity 2C: Scenario Decision ──────────────────────────────────
        Puzzle::updateOrCreate(
            ['mission_id' => $mission2->mission_id, 'activity_order' => 3],
            [
                'topic_id'           => $hwTopic->topic_id,
                'puzzle_type'        => 'scenario',
                'question_text'      => 'Incident Response: A host cannot ping the default gateway — diagnose and remediate.',
                'instruction'        => 'INCIDENT BRIEFING: Host PC-A (192.168.1.50/24) cannot ping its default gateway (192.168.1.1). The switch port LED is amber. Pings to other hosts on the same /24 subnet also fail. Select the most effective first-response action.',
                'activity_data'      => [
                    'scenario_text' => 'Host PC-A (192.168.1.50/24) cannot reach its default gateway at 192.168.1.1. The switch port LED is amber rather than green. Pings to all other hosts on the same subnet also fail. What is the correct first diagnostic and remediation action?',
                    'choices'       => [
                        ['id' => 'choice_1', 'label' => 'Verify physical Layer 1 link: check RJ-45 cable seating, test with a known-good cable, and confirm the port LED turns green.'],
                        ['id' => 'choice_2', 'label' => 'Immediately change the host IP address to a different /16 subnet range without testing the cable first.'],
                        ['id' => 'choice_3', 'label' => 'Reset the entire Layer 2 switch to factory defaults, losing all VLAN and port security configuration.'],
                        ['id' => 'choice_4', 'label' => 'Re-flash the router firmware without backing up the running-config first.'],
                    ],
                ],
                'correct_answer'     => [
                    'correct_choice' => 'choice_1',
                ],
                'hint'               => 'An amber switch LED indicates a Layer 1 physical or duplex mismatch issue. Always verify the physical layer first.',
                'correct_feedback'   => 'Correct diagnosis! The OSI model teaches us to troubleshoot bottom-up — Layer 1 physical problems must be resolved before investigating Layer 3 IP issues.',
                'incorrect_feedback' => 'Incorrect. An amber LED is a Layer 1 signal. Changing IPs or resetting switches will not fix a physical cable fault. Always start at the Physical layer.',
                'points'             => 175,
                'difficulty'         => 'intermediate',
                'is_required'        => true,
                'status'             => 'published',
            ]
        );

        // ── Update mission_id references on the legacy puzzles if still null ─
        Puzzle::whereNull('mission_id')->where('topic_id', $osiTopic->topic_id)->update(['mission_id' => $mission1->mission_id]);
        Puzzle::whereNull('mission_id')->where('topic_id', $hwTopic->topic_id)->update(['mission_id' => $mission2->mission_id]);

        // ════════════════════════════════════════════════════════════════════
        // Achievement Clearance Badges
        // ════════════════════════════════════════════════════════════════════
        $badges = [
            [
                'badge_name'        => 'Packet Tracer Initiate',
                'description'       => 'Completed your first interactive networking puzzle challenge.',
                'xp_reward'         => 50,
                'badge_icon'        => 'fa-network-wired',
                'requirement_type'  => 'missions_completed',
                'requirement_value' => 1,
            ],
            [
                'badge_name'        => 'Clearance Level 2: Packet Analyst',
                'description'       => 'Accumulated 500+ Network XP through verified networking simulations.',
                'xp_reward'         => 100,
                'badge_icon'        => 'fa-shield-halved',
                'requirement_type'  => 'xp_earned',
                'requirement_value' => 500,
            ],
            [
                'badge_name'        => 'OSI Cartographer',
                'description'       => 'Correctly mapped all 7 OSI layers in a single identification mission.',
                'xp_reward'         => 150,
                'badge_icon'        => 'fa-layer-group',
                'requirement_type'  => 'xp_earned',
                'requirement_value' => 250,
            ],
            [
                'badge_name'        => 'Clearance Level 5: Principal Network Architect',
                'description'       => 'Mastered all simulation missions and achieved top security clearance.',
                'xp_reward'         => 500,
                'badge_icon'        => 'fa-award',
                'requirement_type'  => 'xp_earned',
                'requirement_value' => 2000,
            ],
        ];

        foreach ($badges as $badge) {
            Achievement::updateOrCreate(
                ['badge_name' => $badge['badge_name']],
                $badge
            );
        }

        $this->command->info('✅ NetLearn PBL Mission Seeder complete:');
        $this->command->info('   • 2 topics ensured (OSI & Hardware)');
        $this->command->info('   • 2 missions created (Mission 1 per topic)');
        $this->command->info('   • 6 puzzle activities across all 6 archetypes');
        $this->command->info('   • 4 achievement badges upserted');
    }
}
