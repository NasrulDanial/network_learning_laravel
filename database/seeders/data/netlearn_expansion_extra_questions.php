<?php

/*
|--------------------------------------------------------------------------
| Extra quiz questions for the five original seeded stages
|--------------------------------------------------------------------------
| Appended to each stage's quiz so every assessment reaches 15 questions.
| Keyed by topic title. Format: [question, A, B, C, D, correct letter].
| Wording is original; scope follows the CompTIA Network+ N10-009 and Cisco CCNA
| "Introduction to Networks" topic lists (models, devices, addressing, topologies, security).
*/

return [
    'OSI Reference Model & PDU Encapsulation' => [
        ['What is the PDU called at the Network layer?', 'Segment', 'Packet', 'Frame', 'Bits', 'B'],
        ['A frame arrives at a host. Which header and trailer does the host remove first?', 'Transport header', 'Network header', 'Data Link header and trailer', 'Application header', 'C'],
        ['Which two addresses does the Data Link layer add to every frame?', 'Source and destination MAC addresses', 'Source and destination IP addresses', 'Source and destination port numbers', 'Source and destination domain names', 'A'],
        ['HTTP and DNS belong to which OSI layer?', 'Session', 'Transport', 'Application', 'Presentation', 'C'],
        ['Which OSI layer uses port numbers to hand data to the right application?', 'Transport', 'Network', 'Data Link', 'Physical', 'A'],
    ],

    'Network Devices' => [
        ['Which device repeats every incoming signal out all other ports, so all hosts share one collision domain?', 'Switch', 'Router', 'Hub', 'Firewall', 'C'],
        ['A switch reads the source address of each incoming frame. What does it learn from it?', 'Which IP address belongs to which VLAN', 'Which domain name maps to which port', 'Which MAC address is reachable through which port', 'Which route leads to the Internet', 'C'],
        ['Which device boosts a weak signal on a long cable run without understanding the data?', 'Repeater', 'Router', 'Bridge', 'Load balancer', 'A'],
        ['What is special about a Layer 3 switch?', 'It has no MAC address table', 'It can switch frames and also route between subnets or VLANs', 'It only works with wireless clients', 'It repeats signals like a hub', 'B'],
        ['Where is an access control list (ACL) most commonly applied?', 'On a patch panel', 'On a router or firewall interface', 'On a network cable', 'On a hub port', 'B'],
    ],

    'IP Addressing' => [
        ['How many usable host addresses does a /30 subnet provide?', '1', '2', '4', '6', 'B'],
        ['What is the broadcast address of the network 192.168.10.0/24?', '192.168.10.1', '192.168.10.254', '192.168.10.255', '192.168.255.255', 'C'],
        ['What does a subnet mask do?', 'Hides the IP address from other hosts', 'Separates the network part of an address from the host part', 'Encrypts traffic between subnets', 'Assigns a domain name to a host', 'B'],
        ['Which IPv6 address type delivers a packet to exactly one interface?', 'Unicast', 'Broadcast', 'Anycast', 'Multicast', 'A'],
        ['What is the decimal number 192 written as an 8-bit binary octet?', '10101010', '11000000', '11110000', '10000001', 'B'],
    ],

    'Network Topology' => [
        ['Which topology connects each device to exactly two neighbours, forming a closed loop?', 'Star', 'Bus', 'Ring', 'Mesh', 'C'],
        ['What is the main weakness of a star topology?', 'It needs a separate cable for every pair of devices', 'The central switch is a single point of failure', 'Adding a device takes the network down', 'Signals collide on one shared cable', 'B'],
        ['How many links does a full mesh of four devices need?', '4', '6', '8', '12', 'B'],
        ['Which network type connects a phone to a headset a few metres away?', 'PAN', 'LAN', 'MAN', 'WAN', 'A'],
        ['What is a hybrid topology?', 'A network with no cables', 'A combination of two or more topologies', 'A star with only one device', 'A topology used only in data centres', 'B'],
    ],

    'Network Security Basics' => [
        ['What is the safest way for a server to store user passwords?', 'In plain text so they can be recovered', 'As salted, slow hashes', 'Encrypted with a key kept in the same database', 'In the browser cookie', 'B'],
        ['An attacker phones staff pretending to be IT support to get a password. This is:', 'A brute-force attack', 'Social engineering', 'A denial of service', 'A port scan', 'B'],
        ['What does a VPN mainly give you over an untrusted network?', 'A faster connection', 'An encrypted tunnel to a trusted network', 'A new IP subnet', 'Protection from all malware', 'B'],
        ['Which principle says users should only get the access they need to do their job?', 'Least privilege', 'Defence in depth', 'Implicit deny', 'Non-repudiation', 'A'],
        ['Why should security patches be installed promptly?', 'They make the network faster', 'They fix known vulnerabilities attackers already exploit', 'They change the IP addressing scheme', 'They remove the need for a firewall', 'B'],
    ],
];
