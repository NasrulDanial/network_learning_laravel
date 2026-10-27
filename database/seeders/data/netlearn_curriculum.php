<?php

/*
|--------------------------------------------------------------------------
| NetLearn introductory networking curriculum (seed content)
|--------------------------------------------------------------------------
| Sequence follows common introductory courses (Cisco NetAcad "Introduction
| to Networks", CompTIA Network+ objective domain 1.0 "Networking Concepts"):
| models → devices → addressing → topology/media → security.
|
| Puzzle specs are compact; CurriculumSeeder turns them into the same
| activity_data / correct_answer JSON the lecturer puzzle builder produces.
| Canvas limits (800x500): identification ≤ 4 options, matching ≤ 4 pairs,
| scenario ≤ 4 choices, drag-and-drop ≤ 4 items, sequencing ≤ 7 steps,
| classification ≤ 8 items in 2–4 categories.
|
| Quiz question format: [question, A, B, C, D, correct letter].
*/

return [

    // ─────────────────────────────────────────────────────────────────────
    // 1. OSI & TCP/IP MODELS
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'OSI Reference Model & PDU Encapsulation',
        'order' => 1,
        'difficulty' => 'beginner',
        'minutes' => 45,
        'description' => 'Learn the seven layers of the OSI model, how they map to the TCP/IP model, and how data is wrapped in headers as it travels down the stack.',
        'objectives' => [
            'Name the seven OSI layers in order and describe what each one does.',
            'Identify the PDU (data, segment, packet, frame, bits) at each layer.',
            'Explain encapsulation and de-encapsulation.',
            'Map the OSI layers to the four layers of the TCP/IP model.',
            'Use a layered approach to troubleshoot a connectivity problem.',
        ],
        'content' => <<<'TXT'
WHY USE A LAYERED MODEL?
Networking involves many tasks: turning bits into signals, finding the next device, delivering data reliably, and presenting it to an application. A layered model splits these jobs so each layer only talks to the layers directly above and below it. Vendors can build products that work together, and technicians can troubleshoot one layer at a time.

THE SEVEN OSI LAYERS (top to bottom)
• Layer 7 Application – network services used by applications (HTTP, DNS, SMTP).
• Layer 6 Presentation – data format, translation, encryption and compression.
• Layer 5 Session – starts, manages and ends conversations between applications.
• Layer 4 Transport – segments data, uses port numbers, reliable (TCP) or fast (UDP) delivery.
• Layer 3 Network – logical (IP) addressing and routing between networks.
• Layer 2 Data Link – framing, MAC addressing and error detection on the local link.
• Layer 1 Physical – bits as electrical, light or radio signals on the medium.
Memory aid (7 to 1): "All People Seem To Need Data Processing".

ENCAPSULATION
As data moves down the stack, each layer adds its own header (and the Data Link layer also adds a trailer). The protocol data unit (PDU) changes name along the way:
Data (Application) → Segment (Transport) → Packet (Network) → Frame (Data Link) → Bits (Physical).
The receiving host reverses the process (de-encapsulation), removing one header per layer.

OSI VERSUS TCP/IP
The TCP/IP model used on the Internet has four layers:
• Application = OSI Application + Presentation + Session
• Transport = OSI Transport
• Internet = OSI Network
• Network Access = OSI Data Link + Physical

TROUBLESHOOTING BY LAYER
Bottom-up troubleshooting starts with Layer 1 (cables, link lights) and works upward. Top-down starts at the application. Divide-and-conquer starts in the middle, for example with a ping test at Layer 3.
TXT,
        'quiz' => [
            'title' => 'OSI Model Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['How many layers are in the OSI reference model?', '4', '5', '7', '9', 'C'],
                ['Which OSI layer is responsible for logical addressing and routing between networks?', 'Data Link', 'Network', 'Transport', 'Session', 'B'],
                ['What is the TCP protocol data unit (PDU) at the Transport layer called?', 'Frame', 'Packet', 'Bits', 'Segment', 'D'],
                ['At which layer do switches make forwarding decisions using MAC addresses?', 'Data Link (Layer 2)', 'Physical (Layer 1)', 'Network (Layer 3)', 'Application (Layer 7)', 'A'],
                ['Which TCP/IP model layer combines the OSI Application, Presentation and Session layers?', 'Internet', 'Application', 'Transport', 'Network Access', 'B'],
                ['What does encapsulation mean?', 'Removing headers as data moves up the stack', 'Encrypting data before it is sent', 'Adding protocol headers (and a trailer) as data moves down the layers', 'Compressing data to save bandwidth', 'C'],
                ['Which layer handles data formatting, translation, encryption and compression?', 'Presentation', 'Session', 'Application', 'Transport', 'A'],
                ['Which of these is a connectionless Transport layer protocol?', 'TCP', 'UDP', 'ARP', 'Ethernet', 'B'],
                ['Which layer establishes, manages and terminates dialogues between applications?', 'Network', 'Physical', 'Session', 'Data Link', 'C'],
                ['A technician checks cables and link lights before anything else. Which troubleshooting approach is this?', 'Top-down', 'Divide and conquer', 'Follow the path', 'Bottom-up', 'D'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Seven Layers, One Conversation',
                'objective' => 'Put the OSI layers in order and match each layer to its job.',
                'description' => 'A new technician keeps mixing up the OSI layers. Rebuild the model and explain what each layer is responsible for.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'Stack the OSI layers',
                        'instruction' => 'Drag the layers into order from Layer 7 (top) down to Layer 1 (bottom).',
                        'steps' => ['Application', 'Presentation', 'Session', 'Transport', 'Network', 'Data Link', 'Physical'],
                        'hint' => 'Memory aid from Layer 7 down: All People Seem To Need Data Processing.',
                        'explanation' => 'From top to bottom: Application, Presentation, Session, Transport, Network, Data Link, Physical.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Which layer routes packets between networks?',
                        'instruction' => 'Choose the OSI layer responsible for logical (IP) addressing and choosing a path between networks.',
                        'options' => ['Data Link', 'Network', 'Transport', 'Session'],
                        'correct' => 1,
                        'hint' => 'Routers work at this layer and read IP addresses.',
                        'explanation' => 'The Network layer (Layer 3) handles IP addressing and routing.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Match each layer to its job',
                        'instruction' => 'Connect every OSI layer on the left to the function it performs on the right.',
                        'pairs' => [
                            ['Physical', 'Sends bits as signals on the medium'],
                            ['Data Link', 'Frames data and uses MAC addresses'],
                            ['Transport', 'Segments data and uses port numbers'],
                            ['Application', 'Provides network services to user apps'],
                        ],
                        'hint' => 'Think about the PDU at each layer: bits, frames, segments, data.',
                        'explanation' => 'Physical carries bits, Data Link builds frames with MAC addresses, Transport segments data with ports, Application serves user programs.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Encapsulation in Action',
                'objective' => 'Follow a web request as it is wrapped in headers on its way to the wire.',
                'description' => 'A student opens a website. Trace how the request is encapsulated, layer by layer, before it leaves the network card.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'Order the PDUs during encapsulation',
                        'instruction' => 'Arrange the protocol data units in the order they are created as data moves down the stack.',
                        'steps' => ['Data', 'Segment', 'Packet', 'Frame', 'Bits'],
                        'hint' => 'Start at the Application layer and finish on the cable.',
                        'explanation' => 'Data becomes a segment (Transport), then a packet (Network), then a frame (Data Link), then bits (Physical).',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Match each PDU to its layer',
                        'instruction' => 'Connect each protocol data unit to the OSI layer where it is created.',
                        'pairs' => [
                            ['Segment', 'Transport layer'],
                            ['Packet', 'Network layer'],
                            ['Frame', 'Data Link layer'],
                            ['Bits', 'Physical layer'],
                        ],
                        'hint' => 'Packets carry IP addresses; frames carry MAC addresses.',
                        'explanation' => 'Segments belong to Transport, packets to Network, frames to Data Link and bits to Physical.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Which header is added last?',
                        'instruction' => 'A browser request has been segmented and given an IP header. Before the network card turns it into bits, which information is added last?',
                        'choices' => [
                            'A TCP header with source and destination ports',
                            'An Ethernet header and trailer with MAC addresses',
                            'An HTTP header with the page address',
                            'A new IP header with the router address',
                        ],
                        'correct' => 1,
                        'hint' => 'The last step before Layer 1 builds a frame.',
                        'explanation' => 'The Data Link layer adds an Ethernet header and trailer (frame) just before the data is sent as bits.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: OSI Meets TCP/IP',
                'objective' => 'Map OSI layers to the TCP/IP model and troubleshoot by layer.',
                'description' => 'Your team documents networks with the TCP/IP model while textbooks use OSI. Translate between the two and use the layers to solve a fault.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'classification',
                        'title' => 'Place OSI layers in the TCP/IP model',
                        'instruction' => 'Drop each OSI layer into the TCP/IP layer that contains it.',
                        'categories' => ['Application', 'Transport', 'Internet', 'Network Access'],
                        'items' => [
                            ['OSI Application', 0],
                            ['OSI Presentation', 0],
                            ['OSI Session', 0],
                            ['OSI Transport', 1],
                            ['OSI Network', 2],
                            ['OSI Data Link', 3],
                            ['OSI Physical', 3],
                        ],
                        'hint' => 'TCP/IP merges the top three OSI layers and the bottom two.',
                        'explanation' => 'Application = OSI 5–7, Transport = OSI 4, Internet = OSI 3, Network Access = OSI 1–2.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Reliable delivery protocol',
                        'instruction' => 'Which Transport layer protocol provides reliable, connection-oriented delivery with acknowledgements?',
                        'options' => ['UDP', 'IP', 'TCP', 'ICMP'],
                        'correct' => 2,
                        'hint' => 'It uses a three-way handshake.',
                        'explanation' => 'TCP is connection-oriented and acknowledges data; UDP is connectionless.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Start troubleshooting at the right layer',
                        'instruction' => 'A user cannot open any website. You notice the link light on their network card is off. Which layer should you investigate first?',
                        'choices' => [
                            'Application layer: reinstall the browser',
                            'Network layer: change the IP address',
                            'Physical layer: check the cable and port',
                            'Transport layer: open firewall ports',
                        ],
                        'correct' => 2,
                        'hint' => 'No link light means no signal on the medium.',
                        'explanation' => 'A missing link light is a Layer 1 symptom, so check the cable, port and network card first.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 2. NETWORK DEVICES
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Network Devices',
        'order' => 2,
        'difficulty' => 'beginner',
        'minutes' => 40,
        'description' => 'Hubs, switches, routers, access points and firewalls: what each device does, which OSI layer it works at, and how it affects collision and broadcast domains.',
        'objectives' => [
            'Describe the role of hubs, switches, routers, access points, modems and firewalls.',
            'Identify the OSI layer at which each device operates.',
            'Explain how a switch learns MAC addresses and forwards frames.',
            'Distinguish collision domains from broadcast domains.',
        ],
        'content' => <<<'TXT'
END DEVICES AND INTERMEDIARY DEVICES
End devices (PCs, phones, servers, printers) create or receive data. Intermediary devices connect end devices and move data between them.

THE MAIN DEVICES
• Hub (Layer 1) – repeats every signal out of every port. All ports share one collision domain, so it is rarely used today.
• Switch (Layer 2) – forwards frames using a MAC address table. Each port is its own collision domain; the whole switch is one broadcast domain unless VLANs are used.
• Router (Layer 3) – forwards packets between different IP networks using a routing table. Each router interface is a separate broadcast domain. A home router usually also acts as a switch, access point and DHCP server.
• Wireless access point (AP) – connects Wi-Fi clients to the wired LAN.
• Modem – converts digital data for transmission over a carrier line such as DSL, cable or fibre.
• Firewall – permits or blocks traffic according to security rules.

HOW A SWITCH LEARNS
1. A frame arrives on a port.
2. The switch records the frame's source MAC address against that port.
3. It looks up the destination MAC address in its table.
4. If found, it forwards the frame out of that one port; if not, it floods the frame out of every port except the one it came in on.

COLLISION AND BROADCAST DOMAINS
A collision domain is a segment where two devices transmitting at the same time can collide (a problem with hubs and half-duplex links). A broadcast domain is the set of devices that receive each other's broadcasts. Switches split collision domains; routers split broadcast domains.
TXT,
        'quiz' => [
            'title' => 'Network Devices Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['Which device forwards packets between different IP networks?', 'Hub', 'Router', 'Switch', 'Repeater', 'B'],
                ['At which OSI layer does a hub operate?', 'Layer 1 (Physical)', 'Layer 2 (Data Link)', 'Layer 3 (Network)', 'Layer 4 (Transport)', 'A'],
                ['What does a switch use to decide which port to forward a frame out of?', 'The destination IP address', 'The routing table', 'Its MAC address table', 'The DNS cache', 'C'],
                ['What does a switch do with a frame whose destination MAC address is not in its table?', 'Drops the frame', 'Returns it to the sender', 'Sends it to the router only', 'Floods it out all ports except the incoming port', 'D'],
                ['Which device connects wireless clients to a wired LAN?', 'Wireless access point', 'Modem', 'Hub', 'Patch panel', 'A'],
                ['How many broadcast domains does a single 24-port switch with no VLANs create?', '24', '12', '1', '0', 'C'],
                ['Which device permits or blocks traffic based on security rules?', 'Repeater', 'Firewall', 'Hub', 'Bridge', 'B'],
                ['What is the main job of a modem?', 'Assign IP addresses to hosts', 'Scan files for malware', 'Convert digital data for a carrier line such as DSL or cable', 'Store web pages for faster access', 'C'],
                ['On a switch, each port is its own:', 'Broadcast domain', 'Collision domain', 'IP subnet', 'Routing table', 'B'],
                ['Which address does a router use to forward a packet toward its destination network?', 'Destination IP address', 'Destination MAC address', 'Source port number', 'Frame check sequence', 'A'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Know Your Hardware',
                'objective' => 'Identify common network devices and the layer they work at.',
                'description' => 'The lab cupboard has been emptied onto your desk. Sort out what each device is for before you build the network.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'drag_and_drop',
                        'title' => 'Place each device at its OSI layer',
                        'instruction' => 'Drag each device into the OSI layer where it makes its forwarding decisions.',
                        'template_key' => 'device_to_osi_layer',
                        'pairs' => [
                            ['Hub', 'Layer 1 – Physical'],
                            ['Switch', 'Layer 2 – Data Link'],
                            ['Router', 'Layer 3 – Network'],
                            ['Web proxy', 'Layer 7 – Application'],
                        ],
                        'hint' => 'Hubs repeat signals, switches read MAC addresses, routers read IP addresses.',
                        'explanation' => 'Hub = Layer 1, switch = Layer 2, router = Layer 3, and a web proxy works with HTTP at Layer 7.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Which device uses a MAC address table?',
                        'instruction' => 'Choose the device that forwards frames inside a LAN by looking up destination MAC addresses.',
                        'options' => ['Hub', 'Router', 'Switch', 'Modem'],
                        'correct' => 2,
                        'hint' => 'It learns which device is on which port.',
                        'explanation' => 'A switch builds a MAC address table and forwards frames only to the right port.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Match each device to its role',
                        'instruction' => 'Connect each device to the job it does in a network.',
                        'pairs' => [
                            ['Router', 'Forwards packets between networks'],
                            ['Switch', 'Connects LAN devices using MAC addresses'],
                            ['Access point', 'Connects Wi-Fi clients to the wired LAN'],
                            ['Firewall', 'Permits or blocks traffic using rules'],
                        ],
                        'hint' => 'Only one of these devices makes decisions between different IP networks.',
                        'explanation' => 'Routers join networks, switches connect a LAN, access points add Wi-Fi, firewalls enforce rules.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Inside the Switch',
                'objective' => 'Explain how a switch learns MAC addresses and forwards frames.',
                'description' => 'A switch was just powered on with an empty MAC table. Watch how it learns and decide what it does with unknown destinations.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'How a switch handles a frame',
                        'instruction' => 'Put the switch\'s steps in the order they happen when a frame arrives.',
                        'steps' => [
                            'A frame arrives on a switch port',
                            'Switch records the source MAC and port',
                            'Switch looks up the destination MAC',
                            'Switch forwards the frame out the matching port',
                        ],
                        'hint' => 'The switch learns from the source address before it decides where to send the frame.',
                        'explanation' => 'Receive, learn the source MAC, look up the destination MAC, then forward (or flood if unknown).',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Unknown destination MAC',
                        'instruction' => 'PC-A sends a frame to PC-D. The switch has never seen PC-D\'s MAC address. What does the switch do?',
                        'choices' => [
                            'Drops the frame and sends an error to PC-A',
                            'Floods the frame out every port except the one it arrived on',
                            'Sends the frame to the default gateway only',
                            'Holds the frame until PC-D sends something',
                        ],
                        'correct' => 1,
                        'hint' => 'This is called an unknown unicast.',
                        'explanation' => 'Unknown unicast frames are flooded out all ports except the incoming port; the switch learns PC-D\'s MAC when it replies.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Collision domains on a switch',
                        'instruction' => 'A 24-port switch has a device connected to every port. How many collision domains are there?',
                        'options' => ['1', '2', '12', '24'],
                        'correct' => 3,
                        'hint' => 'Each switch port is its own collision domain.',
                        'explanation' => 'Every switch port is a separate collision domain, so 24 ports give 24 collision domains (but one broadcast domain).',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Collision and Broadcast Domains',
                'objective' => 'Choose devices that reduce collisions and contain broadcasts.',
                'description' => 'An old office network is slow and full of collisions. Classify the equipment and recommend an upgrade.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'classification',
                        'title' => 'Sort devices by OSI layer',
                        'instruction' => 'Drop each device into the OSI layer where it primarily operates.',
                        'categories' => ['Layer 1', 'Layer 2', 'Layer 3'],
                        'items' => [
                            ['Hub', 0],
                            ['Repeater', 0],
                            ['Switch', 1],
                            ['Bridge', 1],
                            ['Router', 2],
                            ['Layer 3 switch', 2],
                        ],
                        'hint' => 'Layer 1 devices only regenerate signals; Layer 3 devices read IP addresses.',
                        'explanation' => 'Hubs and repeaters are Layer 1, switches and bridges are Layer 2, routers and Layer 3 switches route at Layer 3.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Which device separates broadcast domains?',
                        'instruction' => 'Broadcasts from the Sales LAN are flooding the Finance LAN. Which device stops broadcasts from crossing between them?',
                        'options' => ['Router', 'Hub', 'Repeater', 'Unmanaged switch'],
                        'correct' => 0,
                        'hint' => 'Broadcasts do not cross Layer 3 boundaries.',
                        'explanation' => 'Routers do not forward broadcasts, so each router interface is its own broadcast domain.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Fix the slow office network',
                        'instruction' => 'Twelve PCs share one old hub and users complain of slow transfers and many collisions. What is the best upgrade?',
                        'choices' => [
                            'Add a second hub to share the load',
                            'Replace the hub with a switch',
                            'Replace all cables with longer ones',
                            'Turn off half of the PCs',
                        ],
                        'correct' => 1,
                        'hint' => 'You need a separate collision domain for each PC.',
                        'explanation' => 'A switch gives every port its own collision domain and allows full-duplex links, removing collisions.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 3. IP ADDRESSING
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'IP Addressing',
        'order' => 3,
        'difficulty' => 'intermediate',
        'minutes' => 60,
        'description' => 'IPv4 address structure, subnet masks and CIDR notation, private and special-use ranges, default gateways, basic subnetting and an introduction to IPv6.',
        'objectives' => [
            'Describe the structure of a 32-bit IPv4 address and its network and host portions.',
            'Recognise private (RFC 1918), loopback and link-local (APIPA) addresses.',
            'Convert between CIDR prefixes and dotted-decimal subnet masks.',
            'Calculate the network address, broadcast address and usable hosts of a subnet.',
            'Explain the role of the default gateway and DHCP.',
        ],
        'content' => <<<'TXT'
IPv4 ADDRESS STRUCTURE
An IPv4 address is 32 bits long, written as four decimal octets (for example 192.168.1.10). The subnet mask decides which bits identify the network and which identify the host. With mask 255.255.255.0, the first three octets are the network (192.168.1) and the last octet is the host (.10).

CIDR NOTATION
A prefix such as /24 states how many bits belong to the network:
• /8 = 255.0.0.0     • /16 = 255.255.0.0
• /24 = 255.255.255.0     • /26 = 255.255.255.192
Usable hosts per subnet = 2^(host bits) − 2 (the network and broadcast addresses cannot be assigned). A /24 has 8 host bits, so 254 usable hosts; a /26 has 6 host bits, so 62.

SPECIAL ADDRESS RANGES
• Private addresses (RFC 1918): 10.0.0.0/8, 172.16.0.0/12 (172.16.0.0–172.31.255.255) and 192.168.0.0/16. They are not routed on the Internet; routers use NAT to reach public networks.
• Loopback: 127.0.0.1 tests the host's own TCP/IP stack.
• Link-local / APIPA (RFC 3927): 169.254.0.0/16 is self-assigned when a host cannot reach a DHCP server.

FINDING THE NETWORK AND BROADCAST ADDRESS
1. Write the IP address and the mask in binary.
2. AND them bit by bit; the result is the network address.
3. Set all host bits to 1 to get the broadcast address.
Shortcut: for 192.168.1.130/26 the block size is 64 (256 − 192), so subnets start at .0, .64, .128 and .192. The host is in 192.168.1.128/26 and the broadcast is 192.168.1.191.

DEFAULT GATEWAY AND DHCP
A host sends traffic for other networks to its default gateway, the local router interface. DHCP automatically gives hosts an IP address, mask, gateway and DNS server.

IPv6 IN BRIEF
IPv6 addresses are 128 bits, written as eight groups of four hexadecimal digits. Leading zeros can be dropped and one run of zero groups can be shortened to "::".
TXT,
        'quiz' => [
            'title' => 'IP Addressing Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['How many bits make up an IPv4 address?', '16', '32', '64', '128', 'B'],
                ['Which of these is a private (RFC 1918) IPv4 address?', '172.16.5.4', '172.32.5.4', '8.8.4.4', '200.10.1.1', 'A'],
                ['A host shows the address 169.254.33.10. What does this most likely mean?', 'It has a public Internet address', 'It is configured as the default gateway', 'It could not obtain an address from a DHCP server', 'It is the loopback address', 'C'],
                ['Which subnet mask is written as /24?', '255.0.0.0', '255.255.0.0', '255.255.255.128', '255.255.255.0', 'D'],
                ['How many usable host addresses are in a /26 subnet?', '62', '64', '30', '126', 'A'],
                ['What is the network address of host 192.168.5.77/27?', '192.168.5.0', '192.168.5.64', '192.168.5.96', '192.168.5.77', 'B'],
                ['Which address tests the local host\'s own TCP/IP stack?', '192.168.1.1', '255.255.255.255', '127.0.0.1', '0.0.0.0', 'C'],
                ['What is the purpose of a default gateway?', 'Translating domain names to IP addresses', 'Forwarding traffic destined for other networks', 'Assigning IP addresses automatically', 'Encrypting traffic inside the LAN', 'B'],
                ['How many bits long is an IPv6 address?', '32', '48', '64', '128', 'D'],
                ['Which protocol automatically assigns IP addresses to hosts?', 'DHCP', 'DNS', 'ARP', 'ICMP', 'A'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Anatomy of an IPv4 Address',
                'objective' => 'Recognise address types and the parts of an IPv4 configuration.',
                'description' => 'The help desk has a list of addresses copied from user screenshots. Work out what kind of address each one is.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'identification',
                        'title' => 'Length of an IPv4 address',
                        'instruction' => 'How many bits are in an IPv4 address?',
                        'options' => ['8 bits', '16 bits', '32 bits', '128 bits'],
                        'correct' => 2,
                        'hint' => 'Four octets of eight bits each.',
                        'explanation' => 'IPv4 uses four 8-bit octets, which is 32 bits. IPv6 uses 128 bits.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => 'Private, public or special-use?',
                        'instruction' => 'Drop each IPv4 address into the correct category.',
                        'categories' => ['Private (RFC 1918)', 'Public', 'Special-use'],
                        'items' => [
                            ['10.4.8.1', 0],
                            ['172.20.1.5', 0],
                            ['192.168.1.10', 0],
                            ['8.8.8.8', 1],
                            ['172.32.0.1', 1],
                            ['127.0.0.1', 2],
                            ['169.254.10.20', 2],
                        ],
                        'hint' => 'The private 172 range only runs from 172.16 to 172.31.',
                        'explanation' => '10.x, 172.16–31.x and 192.168.x are private; 127.0.0.1 is loopback and 169.254.x is link-local; 8.8.8.8 and 172.32.0.1 are public.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Match the term to its meaning',
                        'instruction' => 'Connect each addressing term to what it does.',
                        'pairs' => [
                            ['127.0.0.1 (loopback)', 'Tests the host\'s own TCP/IP stack'],
                            ['169.254.x.x (APIPA)', 'Self-assigned when DHCP fails'],
                            ['Default gateway', 'Router address used to reach other networks'],
                            ['Subnet mask', 'Separates network bits from host bits'],
                        ],
                        'hint' => 'Only one of these is used when a DHCP server cannot be reached.',
                        'explanation' => 'Loopback tests the local stack, APIPA appears when DHCP fails, the gateway leads off the LAN, and the mask splits network from host.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Subnet Masks and CIDR',
                'objective' => 'Convert prefixes to masks and work out host capacity.',
                'description' => 'The network plan uses CIDR notation but the configuration screens want dotted-decimal masks. Translate and size the subnets.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match each prefix to its mask',
                        'instruction' => 'Connect each CIDR prefix to its dotted-decimal subnet mask.',
                        'pairs' => [
                            ['/8', '255.0.0.0'],
                            ['/16', '255.255.0.0'],
                            ['/24', '255.255.255.0'],
                            ['/26', '255.255.255.192'],
                        ],
                        'hint' => 'Each full octet of 1s adds 8 to the prefix.',
                        'explanation' => '/8, /16 and /24 fill whole octets; /26 adds two more bits (128 + 64 = 192) in the last octet.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Usable hosts in a /24',
                        'instruction' => 'How many host addresses can be assigned to devices in a /24 network?',
                        'options' => ['254', '256', '255', '128'],
                        'correct' => 0,
                        'hint' => 'Subtract the network and broadcast addresses.',
                        'explanation' => 'A /24 has 8 host bits: 2^8 = 256, minus the network and broadcast addresses = 254.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Two hosts that cannot talk',
                        'instruction' => 'PC1 is 192.168.10.25/24 and PC2 is 192.168.20.5/24. They are plugged into the same switch but cannot ping each other. Why?',
                        'choices' => [
                            'The switch needs a firmware update',
                            'They are on different networks and need a router',
                            'Their cables are too short',
                            'Ping only works across the Internet',
                        ],
                        'correct' => 1,
                        'hint' => 'Compare the first three octets with a /24 mask.',
                        'explanation' => '192.168.10.0/24 and 192.168.20.0/24 are different networks, so traffic must go through a router (default gateway).',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Subnetting Lab',
                'objective' => 'Calculate network and broadcast addresses and choose a prefix size.',
                'description' => 'You are splitting 192.168.1.0/24 into smaller subnets for lab rooms. Work through the calculations.',
                'difficulty' => 'advanced',
                'pass_mark' => 70,
                'xp_reward' => 200,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'Steps to find the network address',
                        'instruction' => 'Order the steps used to calculate a host\'s network address.',
                        'steps' => [
                            'Write the IP address in binary',
                            'Write the subnet mask in binary',
                            'AND the address and mask bit by bit',
                            'Convert the result to dotted decimal',
                        ],
                        'hint' => 'You need both values in binary before you can combine them.',
                        'explanation' => 'Convert both to binary, AND them, then convert the result back to dotted decimal.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Network address of 192.168.1.130/26',
                        'instruction' => 'Which subnet does host 192.168.1.130/26 belong to?',
                        'options' => ['192.168.1.0', '192.168.1.64', '192.168.1.128', '192.168.1.192'],
                        'correct' => 2,
                        'hint' => 'A /26 creates blocks of 64 addresses.',
                        'explanation' => 'Block size is 64, so subnets start at .0, .64, .128 and .192. Host .130 falls in 192.168.1.128/26.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Pick the smallest prefix for 50 hosts',
                        'instruction' => 'Each lab room needs addresses for 50 devices. Which is the smallest subnet (largest prefix) that fits?',
                        'choices' => ['/24', '/25', '/26', '/27'],
                        'correct' => 2,
                        'hint' => 'Find the first prefix whose usable hosts are at least 50.',
                        'explanation' => '/27 gives only 30 usable hosts; /26 gives 62, which is the smallest that fits 50 devices.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 4. TOPOLOGY & MEDIA
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Network Topology',
        'order' => 4,
        'difficulty' => 'beginner',
        'minutes' => 40,
        'description' => 'Physical and logical topologies, network types from PAN to WAN, and choosing the right cable or wireless medium for the job.',
        'objectives' => [
            'Compare star, bus, ring and mesh topologies and their strengths and weaknesses.',
            'Distinguish physical topology from logical topology.',
            'Classify networks as PAN, LAN, MAN or WAN by scope.',
            'Choose between twisted-pair, fibre-optic, coaxial and wireless media.',
        ],
        'content' => <<<'TXT'
PHYSICAL AND LOGICAL TOPOLOGY
The physical topology is how devices and cables are actually laid out. The logical topology is the path data takes between devices. A network can be wired as a star but behave logically like a bus, as with an old hub.

COMMON TOPOLOGIES
• Star – every device connects to a central switch. Easy to manage and expand; if the central device fails, the whole star stops. Modern Ethernet LANs use star or extended star.
• Bus – all devices share one backbone cable. Cheap, but a single break brings down the segment.
• Ring – each device connects to two neighbours, forming a loop.
• Mesh – devices have several paths to each other. A full mesh is very resilient but needs many links; a partial mesh is a common compromise for WANs.

NETWORK TYPES BY SCOPE
• PAN (personal area network) – devices around one person, such as Bluetooth headphones.
• LAN (local area network) – a home, building or campus.
• MAN (metropolitan area network) – a city or large campus.
• WAN (wide area network) – connects LANs across cities or countries; the Internet is the largest WAN.

TRANSMISSION MEDIA
• Twisted pair (Cat5e/Cat6, RJ-45 connectors) – the usual desk cabling, up to 100 m per run.
• Fibre-optic – light signals, immune to electromagnetic interference, used for long or high-speed links. Single-mode fibre reaches many kilometres; multimode suits shorter runs.
• Coaxial – used by cable broadband providers.
• Wireless (Wi-Fi) – radio signals for mobile devices; convenient but shared and affected by interference and distance.
TXT,
        'quiz' => [
            'title' => 'Network Topology Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['In which topology do all devices connect to a central switch?', 'Bus', 'Ring', 'Star', 'Mesh', 'C'],
                ['What is the main advantage of a full mesh topology?', 'Lowest cabling cost', 'Redundant paths, so one link failure does not isolate a device', 'It is the simplest to configure', 'It needs no network devices', 'B'],
                ['Which network type typically covers a single building or campus?', 'LAN', 'WAN', 'PAN', 'Internet backbone', 'A'],
                ['What is the maximum length of a standard Cat5e/Cat6 twisted-pair Ethernet run?', '10 m', '50 m', '500 m', '100 m', 'D'],
                ['Which medium is immune to electromagnetic interference?', 'Unshielded twisted pair', 'Fibre-optic cable', 'Coaxial cable', 'Shielded twisted pair', 'B'],
                ['What does a logical topology describe?', 'How data flows between devices', 'Where cables are physically installed', 'The colour coding of cables', 'The brand of the switches', 'A'],
                ['A network that connects offices in different cities is a:', 'LAN', 'PAN', 'WAN', 'VLAN', 'C'],
                ['In a bus topology, what happens if the backbone cable breaks?', 'Only one device is affected', 'Traffic reroutes automatically', 'Nothing, because a bus has redundancy', 'The network segment stops working', 'D'],
                ['Which connector is used on standard twisted-pair Ethernet cables?', 'RJ-45', 'RJ-11', 'BNC', 'LC', 'A'],
                ['How are most modern Ethernet LANs physically wired?', 'Ring', 'Star or extended star', 'Bus', 'Full mesh', 'B'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Shapes of a Network',
                'objective' => 'Recognise topologies and their trade-offs.',
                'description' => 'You are reviewing network diagrams for a company that is redesigning its offices. Identify each layout and its weak points.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match topology to description',
                        'instruction' => 'Connect each topology to the description that fits it.',
                        'pairs' => [
                            ['Star', 'Every device connects to one central switch'],
                            ['Bus', 'All devices share a single backbone cable'],
                            ['Ring', 'Each device links to two neighbours in a loop'],
                            ['Mesh', 'Devices have multiple redundant paths'],
                        ],
                        'hint' => 'Picture the shape each name describes.',
                        'explanation' => 'Star has a centre, bus has a backbone, ring forms a loop and mesh has many paths.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Single point of failure',
                        'instruction' => 'In which topology does the failure of one central device stop all communication?',
                        'options' => ['Full mesh', 'Partial mesh', 'Star', 'Ring with a backup link'],
                        'correct' => 2,
                        'hint' => 'Everything plugs into one box.',
                        'explanation' => 'In a star, every device depends on the central switch, so it is a single point of failure.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Keep the branches connected',
                        'instruction' => 'A bank has five branch offices and must keep them connected even if any single WAN link fails. Which design fits best?',
                        'choices' => [
                            'A bus linking the branches in a line',
                            'A star centred on the head office',
                            'A mesh with redundant links between branches',
                            'A single wireless link per branch',
                        ],
                        'correct' => 2,
                        'hint' => 'You need more than one path to every branch.',
                        'explanation' => 'A full or partial mesh gives alternative paths, so one failed link does not isolate a branch.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: From PAN to WAN',
                'objective' => 'Classify networks by geographic scope and understand logical topology.',
                'description' => 'A new client wants to know what kind of networks they already run. Classify each one and explain the terms.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'classification',
                        'title' => 'Classify each network',
                        'instruction' => 'Drop each example into the network type that describes its scope.',
                        'categories' => ['PAN', 'LAN', 'MAN', 'WAN'],
                        'items' => [
                            ['Phone to Bluetooth earbuds', 0],
                            ['Smartwatch to phone', 0],
                            ['Home Wi-Fi network', 1],
                            ['Office floor network', 1],
                            ['City-wide campus links', 2],
                            ['Links between offices in two countries', 3],
                        ],
                        'hint' => 'Think about distance: one person, one building, one city, many cities.',
                        'explanation' => 'Personal devices form a PAN, a home or office is a LAN, a city is a MAN and links between countries form a WAN.',
                    ],
                    [
                        'type' => 'sequencing',
                        'title' => 'Order networks by size',
                        'instruction' => 'Arrange the network types from smallest geographic area to largest.',
                        'steps' => ['PAN', 'LAN', 'MAN', 'WAN'],
                        'hint' => 'Personal, local, metropolitan, wide.',
                        'explanation' => 'PAN (personal) < LAN (local) < MAN (metropolitan) < WAN (wide area).',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'What is a logical topology?',
                        'instruction' => 'Which statement describes a logical topology?',
                        'options' => [
                            'Where the cables run in the building',
                            'The path data takes between devices',
                            'The physical shape of the server rack',
                            'The length of each cable',
                        ],
                        'correct' => 1,
                        'hint' => 'Logical is about how data flows, not where cables are.',
                        'explanation' => 'Logical topology describes how data travels; physical topology describes how devices and cables are laid out.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Cables and Media',
                'objective' => 'Choose the right transmission medium for each situation.',
                'description' => 'The facilities team is planning cabling for a new campus. Recommend the right medium for each link.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'drag_and_drop',
                        'title' => 'Choose the medium for each link',
                        'instruction' => 'Drag each medium to the situation it suits best.',
                        'template_key' => 'medium_to_use_case',
                        'pairs' => [
                            ['Cat6 cable', 'Desk PC to wall socket'],
                            ['Single-mode fibre', 'Link between buildings 10 km apart'],
                            ['Wi-Fi', 'Laptops moving around the office'],
                            ['Coaxial cable', 'Cable broadband to the modem'],
                        ],
                        'hint' => 'Distance and mobility decide most of these.',
                        'explanation' => 'Cat6 suits short desk runs, single-mode fibre covers long distances, Wi-Fi serves mobile devices and coax carries cable broadband.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Maximum twisted-pair length',
                        'instruction' => 'What is the maximum length of a standard Cat5e/Cat6 Ethernet cable run?',
                        'options' => ['10 metres', '100 metres', '500 metres', '2 kilometres'],
                        'correct' => 1,
                        'hint' => 'It is the standard limit for copper Ethernet channels.',
                        'explanation' => 'Twisted-pair Ethernet is limited to 100 m per run; longer links need fibre or another switch.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Cabling past heavy machinery',
                        'instruction' => 'You must connect two buildings 300 m apart. The cable route passes motors that cause strong electrical interference. Which medium should you use?',
                        'choices' => [
                            'Unshielded twisted pair (Cat5e)',
                            'Fibre-optic cable',
                            'Telephone cable',
                            'A long USB cable',
                        ],
                        'correct' => 1,
                        'hint' => 'One medium uses light instead of electricity.',
                        'explanation' => 'Fibre is immune to electromagnetic interference and easily covers 300 m, which exceeds copper\'s 100 m limit.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 5. NETWORK SECURITY BASICS
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Network Security Basics',
        'order' => 5,
        'difficulty' => 'intermediate',
        'minutes' => 50,
        'description' => 'The CIA triad, common threats such as phishing and malware, secure versus insecure protocols and ports, and the basic controls that defend a network.',
        'objectives' => [
            'Explain confidentiality, integrity and availability with examples.',
            'Recognise common threats: malware, social engineering and network attacks.',
            'Identify well-known ports and choose secure protocols over insecure ones.',
            'Describe how firewalls, MFA and user training reduce risk.',
            'Outline the main phases of incident response.',
        ],
        'content' => <<<'TXT'
THE CIA TRIAD
• Confidentiality – only authorised people can read the data (encryption, access control).
• Integrity – data is not changed without detection (hashes, digital signatures).
• Availability – systems and data are there when needed (redundancy, backups, DDoS protection).

COMMON THREATS
• Malware – viruses, worms, trojans, spyware and ransomware (which encrypts files and demands payment).
• Social engineering – tricking people instead of systems: phishing emails, pretexting phone calls, tailgating into buildings.
• Network attacks – denial of service (DoS/DDoS), man-in-the-middle interception, password brute forcing and port scanning.

SECURE AND INSECURE PROTOCOLS
Some older protocols send data, including passwords, in clear text. Prefer the secure alternatives:
• Telnet (23) → SSH (22)      • HTTP (80) → HTTPS (443)
• FTP (20/21) → SFTP (22) or FTPS      • WEP Wi-Fi → WPA2 or WPA3
Other ports to know: DNS 53, DHCP 67/68, SMTP 25, RDP 3389.

DEFENSIVE CONTROLS
• Firewalls apply rules from top to bottom; the first match wins, and an implicit "deny all" at the end blocks anything not permitted.
• Strong passwords plus multi-factor authentication (MFA) stop most account takeovers.
• Patch management and antivirus/endpoint protection reduce malware risk.
• Security awareness training reduces phishing success.
• Defence in depth: use several layers of controls so one failure is not fatal.

INCIDENT RESPONSE
NIST describes four phases: Preparation; Detection and Analysis; Containment, Eradication and Recovery; and Post-Incident Activity (lessons learned).
TXT,
        'quiz' => [
            'title' => 'Network Security Basics Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['Which part of the CIA triad ensures only authorised users can read data?', 'Confidentiality', 'Integrity', 'Availability', 'Accounting', 'A'],
                ['An email pretending to be from your bank asks you to log in through a link. This is:', 'A DDoS attack', 'Phishing', 'Brute force', 'Port scanning', 'B'],
                ['Which protocol should replace Telnet for secure remote management?', 'FTP', 'HTTP', 'SSH', 'SNMPv1', 'C'],
                ['Which port does HTTPS use by default?', '80', '21', '25', '443', 'D'],
                ['Thousands of compromised devices flood a website until it goes offline. Which CIA goal is attacked?', 'Availability', 'Confidentiality', 'Integrity', 'Non-repudiation', 'A'],
                ['Which Wi-Fi security standard is currently recommended?', 'WEP', 'Open network', 'WPA3', 'WPA with TKIP', 'C'],
                ['What does multi-factor authentication (MFA) require?', 'A longer password', 'Two or more different types of proof, such as a password plus a phone code', 'A firewall rule for each user', 'An encrypted hard drive', 'B'],
                ['Malware that encrypts your files and demands payment is called:', 'Adware', 'Spyware', 'A worm', 'Ransomware', 'D'],
                ['What does the implicit deny at the end of a firewall rule list do?', 'Allows all traffic not explicitly blocked', 'Logs only allowed traffic', 'Blocks any traffic not permitted by an earlier rule', 'Deletes unused rules', 'C'],
                ['Which port does DNS use?', '22', '53', '110', '3389', 'B'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: The CIA Triad and Common Threats',
                'objective' => 'Link security goals to their meaning and recognise threat types.',
                'description' => 'The school IT office has received several incident reports. Classify the threats and explain which security goal each one affects.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match each security goal',
                        'instruction' => 'Connect each term to what it protects.',
                        'pairs' => [
                            ['Confidentiality', 'Only authorised people can read data'],
                            ['Integrity', 'Data is not altered without detection'],
                            ['Availability', 'Systems are usable when needed'],
                            ['Authentication', 'Proves a user is who they claim to be'],
                        ],
                        'hint' => 'Encryption protects one goal; backups and redundancy protect another.',
                        'explanation' => 'Confidentiality limits who can read, integrity prevents undetected changes, availability keeps services up, authentication verifies identity.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => 'Classify the threats',
                        'instruction' => 'Drop each threat into the correct category.',
                        'categories' => ['Malware', 'Social engineering', 'Network attack'],
                        'items' => [
                            ['Ransomware', 0],
                            ['Trojan horse', 0],
                            ['Phishing email', 1],
                            ['Pretexting phone call', 1],
                            ['DDoS flood', 2],
                            ['Man-in-the-middle', 2],
                        ],
                        'hint' => 'Social engineering targets people rather than systems.',
                        'explanation' => 'Ransomware and trojans are malware; phishing and pretexting trick people; DDoS and man-in-the-middle attack the network.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Spot the attack',
                        'instruction' => 'A student receives an email that looks like it is from the university, asking them to confirm their password on a linked page. What is this?',
                        'options' => ['Phishing', 'A DDoS attack', 'A worm', 'Port scanning'],
                        'correct' => 0,
                        'hint' => 'It is bait to make the user hand over credentials.',
                        'explanation' => 'Phishing uses fake messages to trick users into revealing credentials or clicking malicious links.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Ports and Protocols',
                'objective' => 'Know well-known ports and choose secure protocols.',
                'description' => 'An audit found several services using outdated protocols. Identify the ports and replace insecure protocols with secure ones.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match port to protocol',
                        'instruction' => 'Connect each well-known port number to its protocol.',
                        'pairs' => [
                            ['Port 22', 'SSH'],
                            ['Port 53', 'DNS'],
                            ['Port 80', 'HTTP'],
                            ['Port 443', 'HTTPS'],
                        ],
                        'hint' => 'Secure web traffic uses the highest number here.',
                        'explanation' => 'SSH uses 22, DNS 53, HTTP 80 and HTTPS 443.',
                    ],
                    [
                        'type' => 'classification',
                        'title' => 'Secure or insecure?',
                        'instruction' => 'Sort each protocol by whether it protects data in transit.',
                        'categories' => ['Secure', 'Insecure (clear text or weak)'],
                        'items' => [
                            ['SSH', 0],
                            ['HTTPS', 0],
                            ['SFTP', 0],
                            ['WPA3', 0],
                            ['Telnet', 1],
                            ['HTTP', 1],
                            ['FTP', 1],
                            ['WEP', 1],
                        ],
                        'hint' => 'Each insecure protocol here has a secure partner in the other list.',
                        'explanation' => 'SSH, HTTPS, SFTP and WPA3 encrypt traffic; Telnet, HTTP and FTP send clear text and WEP is easily broken.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Replace Telnet',
                        'instruction' => 'Switches are managed with Telnet, which sends passwords in clear text. Which protocol should replace it?',
                        'options' => ['FTP', 'SSH', 'HTTP', 'SNMPv1'],
                        'correct' => 1,
                        'hint' => 'It provides an encrypted remote command line.',
                        'explanation' => 'SSH gives the same remote command-line access as Telnet but encrypts the session.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Defending the Network',
                'objective' => 'Match controls to threats and follow an incident response plan.',
                'description' => 'After a security incident, management wants a defence plan. Choose the right controls and order the response steps.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 200,
                'puzzles' => [
                    [
                        'type' => 'drag_and_drop',
                        'title' => 'Match each control to the threat it stops',
                        'instruction' => 'Drag each security control to the threat it is best at reducing.',
                        'template_key' => 'control_to_threat',
                        'pairs' => [
                            ['Firewall', 'Unwanted inbound connections'],
                            ['Antivirus', 'Malware on laptops'],
                            ['MFA', 'Stolen passwords'],
                            ['Staff training', 'Phishing emails'],
                        ],
                        'hint' => 'Think about where each threat starts: the network, the device, the account or the person.',
                        'explanation' => 'Firewalls filter connections, antivirus catches malware, MFA protects accounts with stolen passwords and training helps people spot phishing.',
                    ],
                    [
                        'type' => 'sequencing',
                        'title' => 'Incident response phases',
                        'instruction' => 'Order the NIST incident response phases.',
                        'steps' => [
                            'Preparation',
                            'Detection and analysis',
                            'Containment, eradication and recovery',
                            'Post-incident activity',
                        ],
                        'hint' => 'You cannot contain what you have not detected.',
                        'explanation' => 'NIST: prepare, detect and analyse, contain/eradicate/recover, then review lessons learned.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Secure the office Wi-Fi',
                        'instruction' => 'The office Wi-Fi still uses WEP and an unknown laptop has been seen on the network. What should you do?',
                        'choices' => [
                            'Hide the network name and keep WEP',
                            'Switch to WPA3 (or WPA2) with a strong new passphrase',
                            'Turn on MAC filtering only',
                            'Move the access point to another room',
                        ],
                        'correct' => 1,
                        'hint' => 'WEP can be cracked in minutes, whatever else you do.',
                        'explanation' => 'Replacing WEP with WPA3 (or WPA2) and a new strong passphrase removes the weak encryption; hiding the SSID or MAC filtering are easily bypassed.',
                    ],
                ],
            ],
        ],
    ],
];
