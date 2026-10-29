<?php

/*
|--------------------------------------------------------------------------
| Stages 8-10: new topics
|--------------------------------------------------------------------------
| Scope follows the CompTIA Network+ N10-009 domains (Networking Concepts, Network
| Implementation, Network Operations, Network Troubleshooting) and the Cisco CCNA
| "Introduction to Networks" outline (switching, routing, DHCP, NAT, ICMP, wireless).
| All text is original wording written for this system.
*/

return [

    // ─────────────────────────────────────────────────────────────────────
    // 8. ROUTING, SWITCHING & VLANS
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Routing, Switching & VLANs',
        'order' => 8,
        'difficulty' => 'intermediate',
        'minutes' => 55,
        'description' => 'Split a LAN into VLANs, keep redundant switches loop-free with Spanning Tree, and learn how routers choose the best path.',
        'objectives' => [
            'Explain what a VLAN is and why networks use them.',
            'Tell access ports from trunk ports and describe 802.1Q tagging.',
            'Describe how Spanning Tree Protocol prevents switching loops.',
            'Read a routing table: connected, static and default routes, and longest-prefix matching.',
            'Compare static and dynamic routing and name common routing protocols.',
        ],
        'content' => <<<'TXT'
WHAT IS A VLAN?
A virtual LAN (VLAN) divides one physical switch into several logical Layer 2 networks. Each VLAN is its own broadcast domain, so a broadcast in the Finance VLAN never reaches the Guest VLAN. VLANs shrink broadcast traffic, separate groups of users for security, and let you move a user between groups by changing a port setting instead of moving a cable.

ACCESS PORTS AND TRUNKS
An access port belongs to one VLAN and connects an end device such as a PC or printer. A trunk port carries traffic for many VLANs between switches (or to a router). On a trunk, each frame is tagged with its VLAN ID using the IEEE 802.1Q standard so the far end knows which VLAN it belongs to. Untagged frames on a trunk go to the native VLAN.

TALKING BETWEEN VLANS
Devices in different VLANs sit in different subnets, so they need Layer 3. Options: a router with one trunk link and a sub-interface per VLAN ("router on a stick"), or a Layer 3 switch that routes between VLAN interfaces internally.

SPANNING TREE PROTOCOL (STP)
Redundant links between switches protect against failure, but without a safeguard they create loops: broadcasts circle forever and flood the network (a broadcast storm). STP (IEEE 802.1D, and the faster RSTP 802.1w) lets switches exchange BPDUs, elect a root bridge, work out each switch's best path to it and block redundant ports. If an active link fails, a blocked port is unblocked.

HOW A ROUTER CHOOSES A PATH
A router compares a packet's destination IP address with its routing table and forwards to the next hop of the most specific matching route (the longest prefix wins). Routes come from directly connected networks, static entries typed by an administrator, and dynamic routing protocols. A default route (0.0.0.0/0) matches anything not otherwise listed, which is how a small office points everything unknown at its ISP.

STATIC VERSUS DYNAMIC ROUTING
Static routes are simple, predictable and use no bandwidth, but must be changed by hand. Dynamic protocols such as OSPF (link-state, common inside organisations), RIP (distance-vector, hop count) and BGP (between organisations on the Internet) learn routes from neighbours and adapt when links fail. When several sources offer a route, the lower administrative distance (the trustworthiness of the source) wins; within one protocol, the lower metric (cost) wins.
TXT,
        'quiz' => [
            'title' => 'Routing, Switching & VLANs Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['What is a VLAN?', 'A faster type of Ethernet cable', 'A logical Layer 2 broadcast domain created on a switch', 'A wireless network name', 'A type of IP address', 'B'],
                ['Which port type carries traffic for several VLANs between two switches?', 'Access port', 'Console port', 'Trunk port', 'Loopback port', 'C'],
                ['Which IEEE standard defines VLAN tagging on Ethernet frames?', '802.1Q', '802.3', '802.11', '802.1X', 'A'],
                ['Two hosts in different VLANs need to communicate. What is required?', 'A hub', 'Nothing, VLANs share one broadcast domain', 'A router or Layer 3 switch', 'A longer cable', 'C'],
                ['What does Spanning Tree Protocol prevent?', 'Slow DNS lookups', 'Switching loops and broadcast storms', 'Duplicate IP addresses', 'Weak Wi-Fi signals', 'B'],
                ['Which switch acts as the reference point of a spanning tree?', 'The root bridge', 'The default gateway', 'The DHCP server', 'The trunk', 'A'],
                ['What does a router consult to decide where to forward a packet?', 'Its ARP cache only', 'Its MAC address table', 'Its routing table', 'The DNS cache', 'C'],
                ['A packet matches two routes: 10.0.0.0/8 and 10.1.1.0/24. Which does the router use?', '10.0.0.0/8, because it is first', '10.1.1.0/24, the longest matching prefix', 'Both at once', 'Neither, it drops the packet', 'B'],
                ['What does a default route (0.0.0.0/0) match?', 'Only the local subnet', 'Only multicast traffic', 'Any destination with no more specific route', 'Nothing', 'C'],
                ['Which of these is a dynamic routing protocol?', 'OSPF', 'ARP', 'DHCP', 'ICMP', 'A'],
                ['What is a static route?', 'A route learned from a neighbour', 'A route that never works', 'A route typed in manually by an administrator', 'A route used only for Wi-Fi', 'C'],
                ['What does administrative distance express?', 'The cable length to the next hop', 'How trustworthy the source of a route is (lower is preferred)', 'The number of users on a subnet', 'The speed of an interface', 'B'],
                ['What does "router on a stick" refer to?', 'A router with a broken antenna', 'A router that only routes IPv6', 'Two routers sharing one IP address', 'One trunk link with a sub-interface per VLAN for inter-VLAN routing', 'D'],
                ['An untagged frame arrives on an access port. What happens to it?', 'It is dropped', 'It is broadcast to all VLANs', 'It is routed to the Internet', 'It is placed in the VLAN assigned to that port', 'D'],
                ['Which is a benefit of using VLANs?', 'Smaller broadcast domains and easier segmentation', 'Unlimited bandwidth', 'No need for IP addresses', 'Longer maximum cable length', 'A'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Carve Up the LAN',
                'objective' => 'Learn VLAN vocabulary and decide how to separate groups of users.',
                'description' => 'Finance and the guest Wi-Fi share one flat switch. Use VLANs to give each group its own broadcast domain.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match the VLAN term to its meaning',
                        'instruction' => 'Connect each term to its definition.',
                        'pairs' => [
                            ['VLAN', 'Logical Layer 2 broadcast domain'],
                            ['Access port', 'Carries one VLAN to an end device'],
                            ['Trunk port', 'Carries many VLANs between switches'],
                            ['802.1Q', 'Standard that tags frames with a VLAN ID'],
                        ],
                        'hint' => 'Access = one VLAN, trunk = many.',
                        'explanation' => 'A VLAN is a logical broadcast domain; access ports serve end devices, trunks link switches, and 802.1Q adds the VLAN tag.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'What VLANs cut down',
                        'instruction' => 'What do VLANs primarily reduce?',
                        'options' => ['Cable length', 'Broadcast traffic and exposure between groups', 'Electricity use', 'The size of an IP address'],
                        'correct' => 1,
                        'hint' => 'Each VLAN is a separate broadcast domain.',
                        'explanation' => 'Smaller broadcast domains mean less chatter, and groups in different VLANs cannot see each other\'s Layer 2 traffic.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Finance meets the guest network',
                        'instruction' => 'Finance PCs and guest laptops plug into the same switch and can see each other\'s broadcasts. What should you do?',
                        'choices' => [
                            'Create separate VLANs and route between them only where allowed',
                            'Replace the switch with a hub',
                            'Give everyone the same IP range',
                            'Turn the switch off at night',
                        ],
                        'correct' => 0,
                        'hint' => 'Separate groups at Layer 2, then control what crosses at Layer 3.',
                        'explanation' => 'Separate VLANs isolate broadcasts and let a router or firewall decide which traffic may pass between groups.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Loop-Free and Redundant',
                'objective' => 'See how Spanning Tree protects redundant switch links and sort Layer 2 from Layer 3.',
                'description' => 'Two switches were cabled twice for redundancy and the network collapsed into a broadcast storm. Learn what STP does and who decides what.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 200,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'How Spanning Tree settles',
                        'instruction' => 'Order the steps STP follows to make a loop-free topology.',
                        'steps' => [
                            'Switches exchange BPDUs',
                            'A root bridge is elected',
                            'Each switch finds its best path to the root',
                            'Redundant ports are blocked to stop loops',
                        ],
                        'hint' => 'Elect a leader before anyone picks a path to it.',
                        'explanation' => 'BPDUs let switches elect a root, calculate best paths and block the extra links that would create loops.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'The problem STP solves',
                        'instruction' => 'What problem does Spanning Tree Protocol prevent?',
                        'options' => ['Switching loops and broadcast storms', 'Slow DNS lookups', 'IP address conflicts', 'Weak Wi-Fi coverage'],
                        'correct' => 0,
                        'hint' => 'Redundant links without safeguards let frames circle forever.',
                        'explanation' => 'STP blocks redundant paths so frames cannot loop, but keeps them ready in case an active link fails.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => 'Layer 2 or Layer 3?',
                        'instruction' => 'Place each item at the layer where it belongs.',
                        'categories' => ['Layer 2 (switching)', 'Layer 3 (routing)'],
                        'items' => [['MAC address table', 0], ['VLAN tag', 0], ['Spanning Tree', 0], ['Routing table', 1], ['Default gateway', 1], ['Next-hop IP address', 1]],
                        'hint' => 'MAC addresses and VLANs are local; IP addresses and routes cross networks.',
                        'explanation' => 'Switches use MAC tables, VLAN tags and STP; routers use routing tables, gateways and next-hop IP addresses.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Pick the Best Route',
                'objective' => 'Follow a packet through a router and tell static from dynamic routing.',
                'description' => 'A branch router sends packets the wrong way. Read its routing logic to find out why.',
                'difficulty' => 'advanced',
                'pass_mark' => 70,
                'xp_reward' => 225,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'A router forwards a packet',
                        'instruction' => 'Order what a router does with an incoming packet.',
                        'steps' => [
                            'The packet arrives on an interface',
                            'The router reads the destination IP address',
                            'It finds the longest matching route in its table',
                            'It forwards the packet to the next hop',
                        ],
                        'hint' => 'It needs the destination before it can search.',
                        'explanation' => 'Read the destination, find the most specific route, then send to that route\'s next hop.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'The route of last resort',
                        'instruction' => 'What does a default route (0.0.0.0/0) do?',
                        'options' => ['Blocks all traffic', 'Matches any destination that has no more specific route', 'Assigns IP addresses', 'Encrypts packets'],
                        'correct' => 1,
                        'hint' => 'A /0 prefix matches every address.',
                        'explanation' => 'The default route catches everything not otherwise listed, typically pointing at the ISP.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Static or dynamic?',
                        'instruction' => 'Connect each routing term to its meaning.',
                        'pairs' => [
                            ['Static route', 'Configured by hand'],
                            ['Dynamic routing', 'Learned from neighbours automatically'],
                            ['OSPF', 'Link-state routing protocol'],
                            ['Metric', 'Cost used to compare routes'],
                        ],
                        'hint' => 'Dynamic protocols talk to neighbours; static routes do not.',
                        'explanation' => 'Static routes are manual, dynamic protocols such as OSPF learn and adapt, and a metric ranks routes from the same protocol.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 9. WIRELESS NETWORKING & WI-FI
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Wireless Networking & Wi-Fi',
        'order' => 9,
        'difficulty' => 'intermediate',
        'minutes' => 50,
        'description' => 'Understand how Wi-Fi carries data through the air: standards, frequency bands, access points, and how to secure and troubleshoot a wireless network.',
        'objectives' => [
            'Match Wi-Fi generations to their 802.11 standards.',
            'Compare the 2.4 GHz, 5 GHz and 6 GHz bands and choose non-overlapping channels.',
            'Describe the roles of an SSID, access point, WLAN controller and antenna.',
            'Rank Wi-Fi security protocols from WEP to WPA3.',
            'Diagnose common wireless problems such as interference and dead spots.',
        ],
        'content' => <<<'TXT'
HOW WI-FI WORKS
Wi-Fi sends data as radio waves between a client (laptop, phone) and an access point (AP). Radio is a shared medium, so Wi-Fi uses CSMA/CA: a device listens, waits for a quiet moment and only then sends. The AP bridges wireless clients onto the wired LAN, so Wi-Fi is a Layer 1 and Layer 2 technology that sits under the same IP network as the cabled devices.

WI-FI STANDARDS
IEEE 802.11 defines the standards. 802.11n is Wi-Fi 4, 802.11ac is Wi-Fi 5, 802.11ax is Wi-Fi 6 (and 6E, which adds the 6 GHz band) and 802.11be is Wi-Fi 7. Each generation raises speed and efficiency; Wi-Fi 6 adds OFDMA so one transmission can serve several clients at once.

BANDS AND CHANNELS
• 2.4 GHz – longest range and best at passing through walls, but slower and crowded. Only three channels (1, 6 and 11) do not overlap. Microwave ovens, Bluetooth and cordless phones interfere.
• 5 GHz – faster with many more channels, but shorter range.
• 6 GHz – newest and least crowded, shortest range, needs Wi-Fi 6E or newer.
Neighbouring APs should use non-overlapping channels so they do not talk over each other.

WIRELESS BUILDING BLOCKS
• SSID – the network name clients see (Service Set Identifier).
• Access point – bridges wireless clients to the wired network.
• WLAN controller – manages many APs centrally (channels, power, roaming, firmware).
• Antenna – radiates and receives the signal; its type shapes coverage.
Roaming lets a client move between APs of the same network without losing its session.

SECURING WIRELESS
WEP is broken and must not be used. WPA improved it, WPA2 (AES-CCMP) is the long-time standard, and WPA3 is the current recommendation with stronger key exchange. Use a long passphrase (or 802.1X with a RADIUS server in organisations), change default admin passwords, update firmware, and put guests on a separate VLAN. Hiding the SSID or filtering MAC addresses is not real security. An evil twin is a rogue AP that copies a real network name to capture traffic.

COMMON WIRELESS PROBLEMS
Dead spots (add or move an AP), co-channel interference (re-plan channels), interference from non-Wi-Fi devices (move to 5 GHz), too many clients on one AP (add APs), and wrong credentials or security mode. A site survey maps coverage and interference before and after deployment.
TXT,
        'quiz' => [
            'title' => 'Wireless Networking Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['Which Wi-Fi generation is IEEE 802.11ax?', 'Wi-Fi 4', 'Wi-Fi 5', 'Wi-Fi 6', 'Wi-Fi 7', 'C'],
                ['Which frequency band generally gives the longer range and better wall penetration?', '2.4 GHz', '5 GHz', '6 GHz', '60 GHz', 'A'],
                ['Which 2.4 GHz channels do not overlap with each other?', '1, 2 and 3', '3, 5 and 7', '1, 6 and 11', '2, 4 and 6', 'C'],
                ['What does SSID stand for?', 'Secure System Identity Data', 'Signal Strength Index Display', 'Service Set Identifier', 'Standard Switch Interface Domain', 'C'],
                ['Which device bridges wireless clients onto a wired LAN?', 'Repeater', 'Hub', 'Access point', 'Patch panel', 'C'],
                ['Which Wi-Fi security protocol is the newest and recommended?', 'WEP', 'WPA', 'WPA2', 'WPA3', 'D'],
                ['Which Wi-Fi security option is broken and must not be used?', 'WPA3', 'WPA2-AES', '802.1X', 'WEP', 'D'],
                ['Which everyday device commonly interferes with 2.4 GHz Wi-Fi?', 'A microwave oven', 'A refrigerator', 'A fibre-optic cable', 'A network switch', 'A'],
                ['What is Wi-Fi roaming?', 'Using Wi-Fi in another country', 'A client moving between APs of one network without losing its session', 'Sharing a hotspot from a phone', 'Switching from Wi-Fi to cabled Ethernet', 'B'],
                ['What does a WLAN controller do?', 'Stores web pages', 'Manages many access points centrally', 'Assigns MAC addresses', 'Encrypts email', 'B'],
                ['What is an "evil twin" access point?', 'A backup AP for failover', 'A rogue AP that imitates a real network to capture traffic', 'An AP with two antennas', 'An AP that only works on 6 GHz', 'B'],
                ['What does a wireless site survey show?', 'Which websites users visit', 'Coverage, signal strength and interference in a building', 'The IP addresses of all clients', 'The age of the cabling', 'B'],
                ['Which short-range technology lets you pay by tapping a phone on a reader?', 'NFC', 'Wi-Fi Direct', 'LTE', 'Ethernet', 'A'],
                ['Which band was added by Wi-Fi 6E?', '900 MHz', '2.4 GHz', '5 GHz', '6 GHz', 'D'],
                ['What does OFDMA, introduced in Wi-Fi 6, allow?', 'Longer cables', 'Wired speeds over Wi-Fi', 'Automatic password changes', 'One transmission to serve several clients at once', 'D'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Waves and Bands',
                'objective' => 'Match Wi-Fi generations and choose the right band and channels.',
                'description' => 'The new office floor has patchy Wi-Fi. Start with the radio basics: which standard, which band, which channels.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match each 802.11 standard to its Wi-Fi name',
                        'instruction' => 'Connect every standard to its marketing generation.',
                        'pairs' => [
                            ['802.11n', 'Wi-Fi 4'],
                            ['802.11ac', 'Wi-Fi 5'],
                            ['802.11ax', 'Wi-Fi 6'],
                            ['802.11be', 'Wi-Fi 7'],
                        ],
                        'hint' => 'The generation number climbs with the letters: n, ac, ax, be.',
                        'explanation' => 'n = Wi-Fi 4, ac = Wi-Fi 5, ax = Wi-Fi 6 (and 6E) and be = Wi-Fi 7.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => '2.4 GHz or 5 GHz?',
                        'instruction' => 'Place each characteristic under the band it describes.',
                        'categories' => ['2.4 GHz band', '5 GHz band'],
                        'items' => [['Longer range through walls', 0], ['Crowded by neighbours and microwaves', 0], ['Only three non-overlapping channels', 0], ['Faster but shorter range', 1], ['Many non-overlapping channels', 1], ['Less interference from household devices', 1]],
                        'hint' => 'Higher frequency: faster and roomier but weaker through walls.',
                        'explanation' => '2.4 GHz reaches further but is crowded with only three clean channels; 5 GHz is faster, has many channels and suffers less interference.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Channels that do not collide',
                        'instruction' => 'Which set of 2.4 GHz channels does not overlap?',
                        'options' => ['1, 6 and 11', '1, 2 and 3', '3, 5 and 7', '2, 4 and 6'],
                        'correct' => 0,
                        'hint' => 'Channels are only 5 MHz apart but each signal is about 20 MHz wide.',
                        'explanation' => 'Channels 1, 6 and 11 are far enough apart that APs on them do not interfere with each other.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Build the WLAN',
                'objective' => 'Place wireless components and trace how a client joins a network.',
                'description' => 'You are rolling out Wi-Fi in a three-floor building. Pick the right parts and understand what happens when a laptop connects.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'drag_and_drop',
                        'title' => 'Wireless components and their roles',
                        'instruction' => 'Drag each component to the job it does.',
                        'template_key' => 'wifi_component_to_role',
                        'pairs' => [
                            ['SSID', 'The network name clients see'],
                            ['Access point', 'Bridges wireless clients to the wired LAN'],
                            ['WLAN controller', 'Manages many APs centrally'],
                            ['Antenna', 'Radiates and receives the signal'],
                        ],
                        'hint' => 'One of these is only a label; one is a manager of managers.',
                        'explanation' => 'The SSID is a name, the AP is the bridge, the controller coordinates many APs and the antenna shapes the signal.',
                    ],
                    [
                        'type' => 'sequencing',
                        'title' => 'A client joins the Wi-Fi',
                        'instruction' => 'Order the steps a laptop goes through to get online over Wi-Fi.',
                        'steps' => [
                            'The client scans for networks',
                            'The client selects an SSID',
                            'Authentication succeeds',
                            'Association with the AP completes',
                            'The client receives an IP address from DHCP',
                        ],
                        'hint' => 'It must be allowed on the network before it can ask for an address.',
                        'explanation' => 'Scan, choose, authenticate, associate and only then DHCP gives the client an address.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Dead spot in the meeting room',
                        'instruction' => 'The meeting room on the far side of the floor gets almost no signal. What is the best fix?',
                        'choices' => [
                            'Add an access point nearer the room on a non-overlapping channel',
                            'Change the router password',
                            'Switch the network to WEP',
                            'Move the DNS server',
                        ],
                        'correct' => 0,
                        'hint' => 'Signal weakens with distance and walls.',
                        'explanation' => 'Coverage problems are solved by adding or relocating an AP, using a channel that does not overlap its neighbours.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Lock Down the Air',
                'objective' => 'Rank Wi-Fi security protocols and spot safe and risky wireless practices.',
                'description' => 'An audit found guest devices on the staff Wi-Fi. Harden the wireless network.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 200,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'Wi-Fi security, weakest to strongest',
                        'instruction' => 'Order the Wi-Fi security protocols from the weakest to the strongest.',
                        'steps' => ['WEP', 'WPA', 'WPA2', 'WPA3'],
                        'hint' => 'Each generation was created because the previous one was found lacking.',
                        'explanation' => 'WEP is broken, WPA was a stop-gap, WPA2 became the standard and WPA3 is the current best.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => 'Safe or risky wireless practice?',
                        'instruction' => 'Sort each practice into the right category.',
                        'categories' => ['Safe practice', 'Risky practice'],
                        'items' => [['WPA3 with a long passphrase', 0], ['Updating access point firmware', 0], ['Guests on their own VLAN', 0], ['Keeping the default admin password', 1], ['Relying on a hidden SSID', 1], ['Using WEP for old devices', 1]],
                        'hint' => 'Ask whether an attacker could get past it in minutes.',
                        'explanation' => 'Strong encryption, patched firmware and separation are good practice; default passwords, hidden SSIDs and WEP give a false sense of security.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'The evil twin',
                        'instruction' => 'What is an evil twin access point?',
                        'options' => ['A fake AP that copies a real network name to capture traffic', 'Two APs sharing one channel', 'A backup controller', 'A dual-band radio'],
                        'correct' => 0,
                        'hint' => 'Think of an impostor.',
                        'explanation' => 'An evil twin imitates a legitimate SSID so victims connect to the attacker\'s AP, which can then read or alter their traffic.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 10. NETWORK SERVICES & TROUBLESHOOTING
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Network Services & Troubleshooting',
        'order' => 10,
        'difficulty' => 'advanced',
        'minutes' => 60,
        'description' => 'Learn the services that make networks usable (DHCP, NAT, ARP, SNMP, syslog), the tools that test them, and a step-by-step method for finding faults.',
        'objectives' => [
            'Describe how DHCP leases an address (Discover, Offer, Request, Acknowledge).',
            'Explain what NAT and PAT do and why they exist.',
            'Use ping, traceroute, ipconfig/ip, nslookup and ARP to test connectivity.',
            'Explain the roles of SNMP and syslog in monitoring.',
            'Apply a structured troubleshooting methodology.',
        ],
        'content' => <<<'TXT'
DHCP: ADDRESSES ON DEMAND
A DHCP server hands out IP configuration automatically: address, subnet mask, default gateway and DNS server. The exchange is DORA: the client broadcasts a Discover, a server answers with an Offer, the client broadcasts a Request for that offer and the server sends an Acknowledge. The address is leased for a set time and renewed before it expires. A host that gets no reply self-assigns a 169.254.x.x (APIPA) address, a classic sign that DHCP is unreachable.

NAT AND PAT
Private addresses (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16) are not routed on the Internet. Network Address Translation (NAT) on the edge router rewrites them to a public address. PAT (NAT overload) lets many private hosts share one public address by tracking each conversation with a different port number. NAT also hides the internal addressing from outsiders, but it is not a firewall.

ICMP, PING AND TRACEROUTE
ICMP carries control and error messages. Ping sends an echo request and waits for an echo reply, proving reachability and measuring round-trip time. Traceroute (tracert on Windows) sends packets with growing TTL values so every router on the path reveals itself, showing where traffic stops or slows. "Request timed out" means no reply came back: the host may be down, a firewall may be blocking ICMP, or the path may be broken.

ARP: FROM IP TO MAC
On a LAN, frames need a MAC address. ARP broadcasts "who has 192.168.1.1?" and the owner replies with its MAC address, which the host caches. A stale or poisoned ARP cache can send traffic to the wrong device.

MONITORING AND LOGGING
SNMP lets a management station poll agents on routers, switches and servers for counters and receive traps when something changes. Syslog sends device log messages to one central server so events can be searched and correlated. NetFlow-style tools show who is using the bandwidth.

A TROUBLESHOOTING METHOD
1. Identify the problem (gather information, question users, find what changed).
2. Establish a theory of probable cause (start with the obvious).
3. Test the theory to confirm it.
4. Establish a plan of action and implement the solution.
5. Verify full system functionality and add preventive measures.
6. Document findings, actions and outcomes.
Useful commands: ipconfig /all or ip addr (local settings), ping, tracert or traceroute, nslookup, arp -a, netstat.
TXT,
        'quiz' => [
            'title' => 'Network Services & Troubleshooting Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['What are the four steps of a DHCP lease, in order?', 'Offer, Discover, Acknowledge, Request', 'Discover, Offer, Request, Acknowledge', 'Request, Offer, Discover, Acknowledge', 'Discover, Request, Offer, Acknowledge', 'B'],
                ['What does a DHCP lease time define?', 'The Wi-Fi password lifetime', 'How long a client may keep its assigned address', 'The number of VLANs allowed', 'The cable length', 'B'],
                ['What does NAT do?', 'Translates private addresses to public ones at the network edge', 'Encrypts all Internet traffic', 'Assigns MAC addresses', 'Blocks every incoming connection', 'A'],
                ['How does PAT let many private hosts share one public IP address?', 'By giving each host a new MAC address', 'By tracking each conversation with a different port number', 'By turning off DNS', 'By using only IPv6', 'B'],
                ['Which protocol does the ping command use?', 'ARP', 'DHCP', 'ICMP', 'SNMP', 'C'],
                ['Which command shows each router hop between you and a destination on Windows?', 'ipconfig', 'tracert', 'nslookup', 'netstat', 'B'],
                ['Which protocol finds the MAC address that belongs to an IPv4 address on the local network?', 'DNS', 'ICMP', 'ARP', 'FTP', 'C'],
                ['Which SNMP component runs on a managed device and answers queries?', 'Agent', 'Trap collector', 'Gateway', 'Resolver', 'A'],
                ['What is the purpose of syslog?', 'Assigning IP addresses', 'Collecting device log messages on a central server', 'Translating domain names', 'Encrypting Wi-Fi', 'B'],
                ['Which step comes first in the troubleshooting methodology?', 'Test the theory', 'Document the findings', 'Identify the problem', 'Implement the fix', 'C'],
                ['After a fix works, what should you do?', 'Verify full functionality, then document what you did', 'Close the ticket immediately', 'Restart every device', 'Change the IP addressing scheme', 'A'],
                ['A user can ping 8.8.8.8 but cannot open websites by name. What should you check next?', 'The network cable', 'The DNS settings', 'The VLAN trunk', 'The Wi-Fi channel', 'B'],
                ['Which Windows command displays all local IP configuration, including DNS servers?', 'ipconfig /all', 'ping /all', 'tracert /all', 'arp /all', 'A'],
                ['Two hosts on a LAN have the same IP address. What is the likely symptom?', 'Faster transfers', 'Intermittent connectivity and address-conflict warnings', 'Better security', 'A change in the DNS domain', 'B'],
                ['A ping returns "Request timed out". What does it suggest?', 'No reply came back: the host is down, blocking ICMP, or the path is broken', 'The reply was received instantly', 'DNS is working perfectly', 'The device has a static address', 'A'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Addresses on Demand',
                'objective' => 'Walk through the DHCP lease process and what a lease delivers.',
                'description' => 'A new floor of PCs shows 169.254.x.x addresses. Work out what should have happened.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'The DHCP exchange (DORA)',
                        'instruction' => 'Order the four DHCP messages a client and server exchange.',
                        'steps' => ['Discover', 'Offer', 'Request', 'Acknowledge'],
                        'hint' => 'The word DORA is the memory aid.',
                        'explanation' => 'Discover, Offer, Request, Acknowledge: DORA.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'What a lease means',
                        'instruction' => 'What does the DHCP lease time define?',
                        'options' => ['How long a client may keep its address', 'The Wi-Fi password', 'The default VLAN', 'The routing metric'],
                        'correct' => 0,
                        'hint' => 'You are borrowing the address, not owning it.',
                        'explanation' => 'A lease lets a client use an address for a set time; it must renew before the lease expires.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'What DHCP hands out',
                        'instruction' => 'Connect each setting to its purpose.',
                        'pairs' => [
                            ['IP address', 'Unique identity on the subnet'],
                            ['Subnet mask', 'Separates network and host parts'],
                            ['Default gateway', 'The exit from the subnet'],
                            ['DNS server', 'Resolves names to IP addresses'],
                        ],
                        'hint' => 'Think about what a PC needs in order to leave its own subnet.',
                        'explanation' => 'DHCP supplies an address, mask, gateway and DNS server so a host can communicate immediately.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Translate and Share',
                'objective' => 'Understand NAT and PAT and tell private from public addresses.',
                'description' => 'A branch office has 50 PCs but one public address from its ISP. Make it work.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'identification',
                        'title' => 'Sharing one public address',
                        'instruction' => 'What does NAT overload (PAT) let many private hosts do?',
                        'options' => ['Share one public IP address using different port numbers', 'Each get their own public IP address', 'Skip routing entirely', 'Avoid using DNS'],
                        'correct' => 0,
                        'hint' => 'The router tells conversations apart by something other than the address.',
                        'explanation' => 'PAT maps each private host and port to a unique port on the shared public address.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Fifty PCs, one public IP',
                        'instruction' => 'A branch has 50 PCs and a single public IP address. Which feature on the edge router lets them all reach the Internet?',
                        'choices' => ['PAT (NAT overload)', 'A second hub', 'VLAN trunking', 'Spanning Tree'],
                        'correct' => 0,
                        'hint' => 'It translates many private addresses to one public address.',
                        'explanation' => 'PAT translates all the private source addresses to the one public address and uses port numbers to separate the sessions.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => 'Private or public address?',
                        'instruction' => 'Sort each IPv4 address into private (RFC 1918) or public.',
                        'categories' => ['Private (RFC 1918)', 'Public'],
                        'items' => [['10.4.7.9', 0], ['172.20.1.1', 0], ['192.168.0.50', 0], ['8.8.8.8', 1], ['142.250.80.46', 1], ['172.32.0.5', 1]],
                        'hint' => 'The 172 block is private only from 172.16 to 172.31.',
                        'explanation' => 'RFC 1918 covers 10/8, 172.16/12 (172.16–172.31) and 192.168/16; 172.32.0.5 is just outside that range and is public.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: The Troubleshooting Toolkit',
                'objective' => 'Pick the right tool and follow a structured troubleshooting method.',
                'description' => 'Tickets are piling up: no web, slow links, odd errors. Use the right tool at the right time, in the right order.',
                'difficulty' => 'advanced',
                'pass_mark' => 70,
                'xp_reward' => 225,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match each tool to its job',
                        'instruction' => 'Connect each command to what it does.',
                        'pairs' => [
                            ['ping', 'Tests reachability with ICMP echo'],
                            ['traceroute / tracert', 'Shows each router hop on the path'],
                            ['ipconfig / ip addr', 'Shows the local IP settings'],
                            ['nslookup', 'Queries a DNS server'],
                        ],
                        'hint' => 'One tool talks to the whole path, one only to your own machine.',
                        'explanation' => 'Ping checks reachability, traceroute shows the path, ipconfig reveals local settings and nslookup tests name resolution.',
                    ],
                    [
                        'type' => 'sequencing',
                        'title' => 'The troubleshooting method',
                        'instruction' => 'Put the troubleshooting steps in order.',
                        'steps' => [
                            'Identify the problem',
                            'Establish a theory of probable cause',
                            'Test the theory',
                            'Establish a plan of action and implement it',
                            'Verify full system functionality',
                            'Document findings',
                        ],
                        'hint' => 'You cannot fix what you have not tested, and you document last.',
                        'explanation' => 'Identify, theorise, test, plan and implement, verify and finally document what happened.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Ping works, websites do not',
                        'instruction' => 'A user can ping 8.8.8.8 successfully but cannot open google.com. What should you check?',
                        'choices' => ['Replace the cable', 'The DNS settings', 'Reboot the switch', 'Change the Wi-Fi channel'],
                        'correct' => 1,
                        'hint' => 'Connectivity to an IP address works, so the link is fine.',
                        'explanation' => 'If an IP address responds but names do not, the fault lies in name resolution, so check the DNS server settings.',
                    ],
                ],
            ],
        ],
    ],
];
