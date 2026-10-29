<?php

/*
|--------------------------------------------------------------------------
| In-depth study notes, stages 1-5 (keyed by topic title)
|--------------------------------------------------------------------------
| Sections are split by the Study Notes tab on ALL-CAPS heading lines, so no body line
| may be written entirely in capitals. Scope and ordering follow the CompTIA Network+
| N10-009 domains and the Cisco CCNA "Introduction to Networks" outline; the wording is
| original. See docs/NETLEARN_CONTENT_EXPANSION.md for the sources.
*/

return [

    'OSI Reference Model & PDU Encapsulation' => <<<'TXT'
WHY NETWORKS USE LAYERED MODELS
Sending an email across the world involves hundreds of separate jobs: turning bits into electrical or radio signals, finding the next device on the cable, choosing a route across many networks, making sure nothing is lost, and finally presenting the data to an application. A layered model splits those jobs into layers. Each layer offers a service to the layer above it, uses the service of the layer below it, and does not care how the other layers work internally.
Layering gives four practical benefits. Vendors can build products that interoperate because the boundaries between layers are standardised. A change in one layer (say, moving from copper to fibre) does not force a change in the others. Learning is easier because you study one job at a time. And troubleshooting is faster because you can test layer by layer.
Two models matter. The OSI (Open Systems Interconnection) reference model has seven layers and is the shared vocabulary of the industry. The TCP/IP model has four layers and describes the protocols the Internet actually runs. Technicians use OSI terms ("that is a Layer 2 problem") while the real software follows TCP/IP.

THE SEVEN OSI LAYERS IN DEPTH
• Layer 7, Application: the interface between the network and the program. It is not the program itself; it is the set of protocols the program uses, such as HTTP, HTTPS, DNS, SMTP, IMAP, FTP and DHCP.
• Layer 6, Presentation: agrees on how data is represented. Character encoding (UTF-8), file and media formats (JPEG, MP3), compression and encryption such as the encryption step of TLS are described here.
• Layer 5, Session: opens, maintains and closes dialogues between two applications, including checkpoints so a long transfer can resume.
• Layer 4, Transport: end-to-end delivery between processes. It splits data into segments, uses port numbers to reach the right application, and offers reliable delivery (TCP) or low-overhead delivery (UDP). Flow control and error recovery live here.
• Layer 3, Network: logical addressing and routing between networks. IP addresses identify hosts, and routers forward packets towards the destination network. ICMP, which carries ping and error messages, also works at this layer.
• Layer 2, Data Link: delivery between two devices on the same link. It builds frames, adds MAC (hardware) addresses, detects errors with a frame check sequence and decides who may transmit. Ethernet and Wi-Fi are Layer 2 technologies, and switches and bridges work here.
• Layer 1, Physical: bits on the medium as voltage, light or radio waves. Cables, connectors, hubs, repeaters, network card signalling, voltage levels and data rates belong here.
Memory aids: from Layer 7 down, "All People Seem To Need Data Processing"; from Layer 1 up, "Please Do Not Throw Sausage Pizza Away".

PDUS AND ENCAPSULATION STEP BY STEP
A protocol data unit (PDU) is the name for the data at a given layer. Application data becomes a segment at Layer 4 (a datagram when UDP is used), a packet at Layer 3, a frame at Layer 2 and bits at Layer 1.
Encapsulation works like putting a letter in an envelope, then in a courier bag, then in a van. When a browser sends a request: the application produces data; Transport adds a header with source and destination ports (and, for TCP, sequence numbers); Network adds a header with source and destination IP addresses; Data Link adds a header with source and destination MAC addresses and a trailer with a checksum; Physical sends the result as signals.
De-encapsulation is the reverse. The receiving network card checks the frame trailer and the destination MAC, strips the Layer 2 header and trailer, passes the packet up; IP checks the destination address and strips its header; Transport uses the port number to hand the data to the right application. Each layer only reads its own header and treats everything inside as payload.
On the way across the Internet the IP packet stays the same while the frame is rebuilt at every hop: each router removes the old frame, reads the IP header, and wraps the packet in a new frame with new MAC addresses for the next link. This is why MAC addresses only matter locally while IP addresses matter end to end.

THE TCP/IP MODEL AND HOW IT MAPS TO OSI
The TCP/IP model groups the OSI layers into four.
• Application covers OSI layers 5, 6 and 7.
• Transport equals OSI layer 4 (TCP and UDP).
• Internet equals OSI layer 3 (IP, ICMP, and routing protocols' messages).
• Network Access (also called Link) covers OSI layers 1 and 2 (Ethernet, Wi-Fi, PPP).
Some textbooks show a five-layer hybrid that splits Network Access into Data Link and Physical. When an exam question says "layer", read carefully which model it uses.

WHAT EACH LAYER ADDS TO A PACKET
• Transport header (TCP): source port, destination port, sequence and acknowledgement numbers, flags such as SYN, ACK and FIN, and a window size for flow control. UDP has only ports, a length and a checksum, which is why it is so light.
• Network header (IPv4): version, header length, TTL (time to live, reduced by one at every router so packets cannot loop forever), protocol (6 for TCP, 17 for UDP, 1 for ICMP), source and destination IP addresses.
• Data Link header (Ethernet): destination MAC, source MAC and a type field naming the Layer 3 protocol inside; the trailer is the frame check sequence.
A standard Ethernet frame carries up to 1500 bytes of payload (the maximum transmission unit, MTU). Larger packets must be fragmented or sent with a bigger MTU.

TROUBLESHOOTING WITH THE LAYERS
The layers give a map for finding faults.
• Bottom-up: start at Layer 1 (is the cable plugged in, is the link light on, is the port enabled?) and climb. Best when the whole connection is dead.
• Top-down: start at the application (does the browser fail only for one website?) and descend. Best when one application misbehaves.
• Divide and conquer: start in the middle, usually with a ping at Layer 3, then move up or down depending on the result.
Typical symptom-to-layer clues: no link light points to Layer 1; a wrong or duplicate MAC or a VLAN mismatch points to Layer 2; an APIPA address (169.254.x.x), wrong subnet mask or missing gateway points to Layer 3; a connection that opens but hangs or a blocked port points to Layer 4; a certificate warning or a 404 page points to Layer 6 or 7.

COMMON MISTAKES AND EXAM TRAPS
• Switches are Layer 2 devices, but a Layer 3 switch also routes. Routers are Layer 3, hubs and repeaters Layer 1.
• Ports belong to Layer 4, IP addresses to Layer 3, MAC addresses to Layer 2. Do not mix them up.
• "Application layer" means protocols such as HTTP, not applications such as Chrome.
• A frame has a header and a trailer; segments and packets have only headers.
• Encryption can happen at several layers (Wi-Fi encryption at Layer 2, IPsec at Layer 3, TLS above Layer 4), so check which one the question means.
• The OSI model is a reference, not a protocol suite: you cannot install it, but you can map any protocol to it.

KEY TERMS
Encapsulation: adding headers (and a trailer) as data moves down the stack. De-encapsulation: removing them on the way up. PDU: the unit of data at a layer. MTU: the largest payload a link carries in one frame. TTL: a hop limit stopping routing loops. Port: a number identifying an application at Layer 4. MAC address: a 48-bit hardware address used on the local link.
TXT,

    'Network Devices' => <<<'TXT'
WHAT NETWORK DEVICES DO
Every device on a network has a job at a particular layer. Some simply move signals, some make decisions using MAC addresses, some use IP addresses, and some inspect the content of traffic. Choosing the right device decides how much traffic a network can carry, how far it can reach and how secure it is.

REPEATERS AND HUBS (LAYER 1)
A repeater regenerates a weakened signal so a cable run can be longer than the medium normally allows. A hub is a multiport repeater: whatever arrives on one port is copied to every other port. Hubs do not read addresses, so all attached devices share one collision domain and must take turns using CSMA/CD (listen, send, and back off randomly after a collision). Half-duplex hubs waste bandwidth, are insecure (every host sees every frame) and have been replaced by switches.

BRIDGES AND SWITCHES (LAYER 2)
A switch is a multiport bridge. It builds a MAC address table by reading the source address of each frame and recording the port it arrived on. For each frame it then decides:
• Known unicast destination: forward out that single port.
• Unknown unicast or a broadcast: flood out every port except the one it arrived on.
• Destination on the same port it arrived on: filter (drop).
Because every port is its own collision domain and links can run full duplex, switches remove collisions and give each device dedicated bandwidth. Switches still forward broadcasts, so a switch without VLANs is a single broadcast domain. Managed switches add VLANs, port security (limit the MAC addresses allowed on a port), Spanning Tree, link aggregation (combining ports into one logical link), port mirroring for packet capture and Power over Ethernet (PoE) to power phones, cameras and access points over the cable.
Entries in the MAC table age out after a few minutes, so moving a laptop to another port quickly updates the table.

ROUTERS (LAYER 3)
A router connects different IP networks and forwards packets between them using a routing table. Each interface is its own broadcast domain, so routers stop broadcasts. A home router combines several functions: a router, a small switch, a wireless access point, a DHCP server, a NAT gateway and a basic firewall. Routers also run routing protocols, apply access control lists and can provide VPN tunnels.
A Layer 3 switch switches frames at wire speed and also routes between VLANs or subnets inside the building, so it is the usual choice for inter-VLAN routing in campus networks.

WIRELESS ACCESS POINTS AND CONTROLLERS
An access point (AP) bridges wireless clients onto the wired LAN. In small networks each AP is configured by hand. In large networks a WLAN controller (hardware or cloud) manages many lightweight APs centrally: channels, power levels, roaming, security policy and firmware. Because the AP sits at Layer 2, it does not route; the router or Layer 3 switch behind it does.

FIREWALLS, IDS AND IPS
A firewall permits or denies traffic by rules. A packet filter checks addresses, ports and protocols. A stateful firewall also remembers established connections so return traffic is allowed automatically. A next-generation firewall adds application awareness, user identity and threat feeds. Rule lists end with an implicit deny: anything not allowed is dropped. An intrusion detection system (IDS) watches traffic and alerts; an intrusion prevention system (IPS) sits inline and can block. Place a firewall at the edge, and consider a screened subnet (DMZ) for public servers.

OTHER COMMON DEVICES
• Modem: converts between the digital signals of your network and the medium of the provider (cable, DSL, fibre ONT).
• Load balancer: spreads client requests over several servers and removes a failed one from rotation.
• Proxy server: makes requests on behalf of clients, caching and filtering content.
• VoIP phones, IP cameras, printers and IoT sensors are endpoints that depend on the infrastructure above, often powered by PoE.
• Network interface card (NIC): the endpoint's connection, with its own MAC address.

COLLISION DOMAINS AND BROADCAST DOMAINS REVISITED
Count the domains by following the rules. A hub does not split anything. A switch splits collision domains per port. Only a router interface (or a VLAN) splits a broadcast domain. A 24-port switch with no VLANs therefore has 24 collision domains and one broadcast domain; a router with three interfaces has three broadcast domains.

CHOOSING A DEVICE
Need to extend a cable run? A repeater, or better a switch. Need to connect many local devices at speed? A switch. Need to join two subnets or reach the Internet? A router. Need to let phones and laptops roam? Access points on a controller. Need to filter by rule? A firewall. When a scenario describes slow performance caused by constant broadcasts, the answer is smaller broadcast domains (VLANs and routing), not faster cabling.

COMMON MISTAKES AND EXAM TRAPS
• Hubs, repeaters and switches do not route; only routers and Layer 3 switches do.
• A switch does not break up broadcasts; a router does.
• Access points are not routers, even though home devices combine both.
• A firewall's implicit deny means unlisted traffic is blocked, so order and specificity of rules matter.
• PoE delivers power, not extra data capacity.
TXT,

    'IP Addressing' => <<<'TXT'
WHAT AN IP ADDRESS IS
An IP address identifies a network interface at Layer 3 and tells routers where to deliver a packet. It has two parts: the network portion, shared by every host on the same subnet, and the host portion, unique within it. The subnet mask (or prefix length) says where the boundary lies.

IPV4 STRUCTURE AND BINARY
An IPv4 address is 32 bits, written as four 8-bit octets in dotted decimal, such as 192.168.10.77. Each octet is 0 to 255 because eight bits give 256 values. To convert, use the place values 128, 64, 32, 16, 8, 4, 2, 1. For example 192 is 128 + 64, which is 11000000, and 168 is 128 + 32 + 8, which is 10101000.
A mask of 255.255.255.0 is 24 ones followed by 8 zeros, written /24. The bits set to one mark the network portion, so a bitwise AND of the address and the mask gives the network address.

ADDRESS CLASSES AND SPECIAL RANGES
Classful addressing is historical but still appears on exams: Class A starts 1 to 126 (default /8), Class B 128 to 191 (/16), Class C 192 to 223 (/24), Class D 224 to 239 is multicast and Class E 240 to 255 is reserved. Real networks now use classless addressing (CIDR) with any prefix length.
Private ranges (RFC 1918) are not routed on the Internet: 10.0.0.0/8, 172.16.0.0/12 (172.16.0.0 to 172.31.255.255) and 192.168.0.0/16. Other special blocks: 127.0.0.0/8 loopback (127.0.0.1 tests the local TCP/IP stack), 169.254.0.0/16 link-local APIPA addresses a host gives itself when DHCP fails, and 224.0.0.0/4 multicast. 0.0.0.0 means "this host" or "any address" and 255.255.255.255 is the limited broadcast.

UNICAST, BROADCAST AND MULTICAST
Unicast is one sender to one receiver. A directed broadcast reaches every host on a subnet using the subnet's last address. Multicast sends to a group that has joined. IPv6 has no broadcast: it uses multicast and anycast (the nearest of several servers sharing an address).

SUBNETTING STEP BY STEP
Subnetting divides one network into smaller ones, saving addresses and shrinking broadcast domains.
1. Write the prefix length. A /26 has 26 network bits and 6 host bits.
2. Host count: 2 to the power of host bits, minus 2 (the network and broadcast addresses). Six host bits give 64 addresses and 62 usable hosts.
3. Block size: 256 minus the interesting octet of the mask. A /26 mask is 255.255.255.192, so subnets step by 64: 0, 64, 128, 192.
4. For host 192.168.1.130/26, the block starting at 128 is the network (192.168.1.128), the next block start minus one is the broadcast (192.168.1.191), and usable hosts run from .129 to .190.
Quick references: /24 gives 254 hosts, /25 gives 126, /26 gives 62, /27 gives 30, /28 gives 14, /29 gives 6 and /30 gives 2 (typical for router-to-router links). To fit N hosts, choose the smallest prefix whose usable count is at least N: 50 hosts needs a /26.
Variable length subnet masking (VLSM) lets you use different prefix lengths in one network, giving big subnets to big departments and /30 links to point-to-point connections.

DEFAULT GATEWAY, ARP AND DHCP
A host compares a destination with its own network using the mask. If the destination is local, it uses ARP to find the destination's MAC address and sends a frame directly. If it is remote, it sends the frame to the default gateway's MAC address with the remote IP address inside; the router takes it from there. A missing or wrong gateway is a classic cause of "I can reach the printer but not the Internet".
DHCP supplies the address, mask, gateway and DNS server automatically, using the Discover, Offer, Request, Acknowledge exchange. Static addresses are used for servers, printers and network devices that must not change, often with DHCP reservations as an alternative.

IPV6 BASICS
IPv6 addresses are 128 bits written as eight groups of four hexadecimal digits, for example 2001:0db8:0000:0000:0000:0000:0000:0001. Leading zeros in a group may be dropped and one run of all-zero groups may be replaced once by a double colon, giving 2001:db8::1. The usual subnet prefix is /64, so the host part is 64 bits. Address types: global unicast (2000::/3), link-local (fe80::/10, always present on an interface), unique local (fc00::/7, like private IPv4), multicast (ff00::/8) and the loopback ::1. IPv6 removes the need for NAT, uses Neighbor Discovery instead of ARP and lets hosts configure themselves with SLAAC.

COMMON MISTAKES AND EXAM TRAPS
• 172.32.x.x is public: the private 172 range stops at 172.31.
• 169.254.x.x means DHCP failed; it is not a valid Internet address.
• Remember to subtract two for usable hosts (except on /31 point-to-point links, and /32 host routes).
• Two hosts with the same IP address cause intermittent connectivity.
• Two hosts that look close numerically may be on different subnets; always apply the mask.
• IPv6 double colons may only appear once in an address.
TXT,

    'Network Topology' => <<<'TXT'
PHYSICAL VERSUS LOGICAL TOPOLOGY
A topology describes how devices are connected. The physical topology is the real layout of cables, devices and rooms. The logical topology is how data actually flows. A network can be wired as a star yet behave logically like a bus: old hub-based Ethernet was physically a star but logically a shared bus because a hub repeated everything to every port.

BUS TOPOLOGY
All devices share one backbone cable (classic coaxial Ethernet, 10BASE2 and 10BASE5), with terminators at both ends to stop signal reflections. It uses little cable and is cheap, but a break anywhere splits or kills the network, collisions rise as devices are added and faults are hard to find. It is obsolete for LANs.

RING TOPOLOGY
Each device connects to exactly two neighbours, forming a loop. Token Ring passed a token so only the holder could transmit, which gave predictable access. FDDI used two counter-rotating fibre rings so the network survived a single break. Rings appear today in metro and carrier networks, where a ring offers an alternate path in both directions.

STAR TOPOLOGY
Every device connects to a central switch. It is the standard LAN design because a cable failure only affects one device, adding a device is easy and faults are simple to find. The disadvantage is the central device: if it fails, everything on it fails, so important switches are duplicated or stacked.

EXTENDED STAR AND HIERARCHICAL DESIGN
Campus networks join stars together in tiers. The three-tier model has an access layer (where users and phones plug in), a distribution layer (policy, VLAN routing, aggregation) and a core layer (fast, highly available transport). Smaller sites use a collapsed core where distribution and core are one device. Data centres use spine and leaf, in which every leaf switch connects to every spine switch so any two servers are the same number of hops apart.

MESH TOPOLOGY
In a full mesh every device connects to every other, so the number of links for n devices is n times (n minus 1) divided by 2: four devices need 6 links, ten need 45. It gives the best redundancy and the highest cost. A partial mesh connects only the most important nodes in this way. WAN backbones and the Internet itself are partial meshes.

HYBRID, POINT-TO-POINT AND HUB-AND-SPOKE
Hybrid networks combine topologies. Point-to-point links connect exactly two sites (a leased line or a router-to-router connection). Hub-and-spoke WANs link every branch to a central headquarters; it is economical but sends branch-to-branch traffic through the hub and depends on it. Dynamic multipoint VPNs create direct tunnels between branches when needed.

NETWORK TYPES BY SIZE
• PAN, personal area network: a few metres (Bluetooth headset, phone tethering).
• LAN, local area network: one building or campus, high speed, owned by the organisation.
• MAN, metropolitan area network: a city or large campus.
• WAN, wide area network: across cities and countries, normally using carriers (MPLS, leased lines, the Internet with VPNs).
• WLAN, SAN and CAN also appear: wireless LAN, storage area network and campus area network.
Other terms: intranet (private web services), extranet (shared with partners) and the Internet.

CABLES AND MEDIA
• Unshielded twisted pair (UTP): the common LAN cable. Pairs are twisted to cancel interference. Category 5e supports 1 Gbps, Cat6 up to 10 Gbps over short runs, Cat6a 10 Gbps to 100 m. Maximum run length is 100 metres (90 m permanent link plus patch cords). Connector: RJ-45. Wiring standards T568A and T568B; a straight-through cable uses the same standard at both ends and a crossover uses A at one end and B at the other.
• Shielded twisted pair (STP): foil or braid reduces electromagnetic interference near motors and power cables.
• Coaxial: a central conductor with shielding, still used for cable-TV broadband.
• Fibre-optic: light through glass. Multimode fibre (shorter reach, cheaper optics, LED or VCSEL) for buildings and data centres; single-mode fibre (very long distances, lasers) for campuses and carriers. It is immune to electromagnetic interference, cannot be tapped easily and offers the highest speeds. Connectors include LC, SC and ST.
• Wireless: radio and microwave, needing no cable but sharing the medium.

CHOOSING A TOPOLOGY AND MEDIUM
Ask four questions: how much redundancy is needed, how big is the site, what is the budget and what interference is present. A noisy factory floor calls for shielded cable or fibre. Runs over 100 metres need fibre or an extra switch. Critical links get redundancy with Spanning Tree or routing protocols.

COMMON MISTAKES AND EXAM TRAPS
• A star is the physical layout of modern Ethernet even though a switch's logical behaviour may differ.
• Full mesh link count is n(n-1)/2, not n squared.
• Twisted pair is limited to 100 metres, fibre is not.
• Only fibre is immune to electromagnetic interference; shielded copper only reduces it.
• Hubs create a logical bus; switches create logical point-to-point connections.
TXT,

    'Network Security Basics' => <<<'TXT'
THE CIA TRIAD AND THE GOALS OF SECURITY
Network security protects three properties. Confidentiality: only authorised people can read data (encryption, access control). Integrity: data is not changed without detection (hashing, digital signatures, checksums). Availability: services work when needed (redundancy, backups, DDoS protection). Many extra terms support them: authentication (who are you), authorisation (what may you do), accounting (what did you do), together called AAA, and non-repudiation (you cannot deny an action you took).

THREATS, VULNERABILITIES AND RISK
A threat is something that can cause harm (an attacker, malware, a flood). A vulnerability is a weakness (an unpatched server, a default password). A risk is the likelihood of a threat exploiting a vulnerability multiplied by the damage. Security work lowers risk by removing vulnerabilities, reducing threats or limiting the impact.

COMMON ATTACKS
• Phishing and social engineering: tricking people. Spear phishing targets individuals, vishing uses voice calls and smishing uses text messages. Pretexting invents a believable story and tailgating follows someone through a secure door.
• Malware: viruses attach to files, worms spread by themselves across networks, trojans pretend to be useful, ransomware encrypts files for payment, spyware and keyloggers steal information, and rootkits hide deep in the system.
• Denial of service: floods a target so legitimate users cannot reach it. A distributed attack (DDoS) uses thousands of compromised devices (a botnet).
• Man-in-the-middle: the attacker sits between two parties to read or alter traffic. ARP poisoning and rogue access points enable it on a LAN.
• Password attacks: brute force tries every combination, dictionary attacks try common words, credential stuffing reuses leaked passwords, and rainbow tables reverse unsalted hashes.
• Injection and web attacks: SQL injection and cross-site scripting exploit applications that trust user input.
• Spoofing and DNS attacks: forging addresses or poisoning DNS caches to redirect victims.

DEFENCE IN DEPTH AND LEAST PRIVILEGE
No single control is enough, so layers are used. Physical security (locks, badges) protects equipment; network controls (firewalls, segmentation, VLANs) limit movement; host controls (patching, antivirus, disk encryption) harden endpoints; application controls validate input; people controls (training, policies) reduce mistakes. Least privilege gives users only the access needed for their job, and separation of duties stops one person controlling a whole process.

AUTHENTICATION AND ACCESS CONTROL
Authentication factors are something you know (password), something you have (token, phone, smart card), something you are (fingerprint, face) and sometimes somewhere you are. Multi-factor authentication (MFA) combines at least two different kinds, so a stolen password alone is not enough. Single sign-on lets one login reach many services. Servers must store passwords as salted, slow hashes, never in plain text. Authorisation models include role-based access control (permissions follow job roles) and access control lists.

FIREWALLS AND NETWORK SEGMENTATION
A firewall enforces rules between zones. Typical zones are inside (trusted LAN), outside (the Internet) and a screened subnet or DMZ for public servers such as web and mail, so a compromised server cannot easily reach the internal network. Rules are processed top-down and end with an implicit deny. Stateful inspection tracks connections. Segmentation with VLANs and access control lists limits what an infected PC can reach, and network access control checks a device's health before it joins.

ENCRYPTION, HASHING AND VPNS
Symmetric encryption (AES) uses one shared key and is fast. Asymmetric encryption (RSA, elliptic curves) uses a public and a private key and solves key exchange, so it is used to start sessions and for digital signatures. Hashing (SHA-256) produces a fixed-size fingerprint that cannot be reversed and shows if data changed. A certificate binds a public key to an identity, vouched for by a certificate authority.
A virtual private network (VPN) builds an encrypted tunnel across an untrusted network. Site-to-site VPNs join offices; remote-access VPNs connect users. IPsec works at Layer 3 and TLS-based VPNs above Layer 4.

SECURE PROTOCOLS AND PORTS
Replace insecure protocols with secure ones: Telnet (port 23) with SSH (22); FTP (21) with SFTP or FTPS; HTTP (80) with HTTPS (443); plain SNMPv1 or v2 with SNMPv3; POP3, IMAP and SMTP with their TLS versions. DNS uses port 53, DHCP uses 67 and 68, and NTP 123. Turning off unused services shrinks the attack surface.

WIRELESS AND ENDPOINT SECURITY
Use WPA3 (or WPA2 with AES) with a strong passphrase, or WPA2/WPA3-Enterprise with 802.1X and a RADIUS server, and never WEP. Hiding the SSID or filtering MAC addresses is not security. Endpoints need operating system and application patches, endpoint protection, full-disk encryption and least-privilege accounts. Patch management is one of the cheapest and most effective controls because attackers exploit known flaws.

INCIDENT RESPONSE AND MONITORING
Log events centrally (syslog), watch for anomalies with an IDS or IPS and a security information and event management (SIEM) system, and have a plan. NIST describes four phases: preparation; detection and analysis; containment, eradication and recovery; and post-incident activity (lessons learned). Backups follow the 3-2-1 idea: three copies, on two kinds of media, with one stored off site, and must be tested by restoring.

COMMON MISTAKES AND EXAM TRAPS
• Encryption protects confidentiality; hashing protects integrity; availability needs redundancy.
• MFA needs different factor types: two passwords are still one factor.
• A firewall does not stop an attack that arrives through a permitted port or by phishing.
• Hidden SSIDs and MAC filtering are weak; WPA3 or WPA2-AES is the right answer.
• HTTPS port 443, SSH 22, DNS 53, Telnet 23: memorise the common ports.
• Implicit deny means anything not explicitly permitted is blocked.
TXT,

];
