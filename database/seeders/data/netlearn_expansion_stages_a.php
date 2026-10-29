<?php

/*
|--------------------------------------------------------------------------
| Stages 2 and 7: topics that may already exist in a database
|--------------------------------------------------------------------------
| Titles match the topics already present in the development database, so the seeder
| tops those up (quiz to 15 questions, missions, notes sections) instead of duplicating them.
| On a fresh database they are created in full.
|
| Quiz question order matters when topping up: the first questions that are not already
| in the quiz are added until it holds 15, so each list starts with the topic's core ideas.
| Puzzle limits for the 800x500 canvas are listed in netlearn_curriculum.php.
*/

return [

    // ─────────────────────────────────────────────────────────────────────
    // 2. NETWORK INFRASTRUCTURE & BROADCAST DOMAINS (with the TCP/IP model)
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'Network Infrastructure & Broadcast Domains',
        'order' => 2,
        'difficulty' => 'intermediate',
        'minutes' => 45,
        'description' => 'Distinguish Layer 1 physical signals, Layer 2 MAC switching fabrics, and Layer 3 IP routing gateways, then see how the TCP/IP model ties them together.',
        'objectives' => [
            'Tell collision domains and broadcast domains apart and name the device that separates each.',
            'Explain how a switch learns MAC addresses and forwards, floods or filters a frame.',
            'Describe why large flat networks suffer from broadcast traffic.',
            'Map common protocols to the four TCP/IP layers.',
            'Explain the TCP three-way handshake and when UDP is the better choice.',
        ],
        'content' => <<<'TXT'
COLLISION DOMAINS VERSUS BROADCAST DOMAINS
A collision domain is a segment where two devices sending at the same moment would corrupt each other's frames. A hub puts every port in one collision domain; a switch gives every port its own. A broadcast domain is the set of devices that receive a Layer 2 broadcast (destination FF:FF:FF:FF:FF:FF). Switches forward broadcasts out every port, so only a router (or a VLAN boundary) splits a broadcast domain.

HOW A SWITCH BUILDS ITS MAC TABLE
1. A frame arrives; the switch records the source MAC address against the port it came in on.
2. It looks up the destination MAC address.
3. Known destination: forward out that one port. Unknown destination or broadcast: flood out every port except the one it arrived on. Destination on the same port: filter (drop).
Entries age out after a few minutes, so the table keeps itself current without any configuration.

WHY BIG FLAT NETWORKS STRUGGLE
Every broadcast (ARP requests, DHCP discovers, some service announcements) interrupts every host in the domain. With hundreds of hosts in one domain the background chatter wastes bandwidth and CPU, and a loop can turn broadcasts into a storm. Splitting the network into several subnets or VLANs joined by a router keeps broadcasts local.

THE TCP/IP MODEL IN ONE PAGE
• Application – HTTP, DNS, SMTP, DHCP (OSI layers 5–7).
• Transport – TCP (reliable, connection-oriented) and UDP (fast, connectionless); port numbers pick the application.
• Internet – IP addressing and routing; ICMP carries error and diagnostic messages.
• Network Access – Ethernet and Wi-Fi framing and signalling (OSI layers 1–2).

TCP OR UDP?
TCP sets up a session with a three-way handshake (SYN, SYN-ACK, ACK), numbers its segments and retransmits losses, so web pages, email and file transfers use it. UDP skips all that overhead, so DNS queries, voice and live video use it and tolerate the odd lost packet.
TXT,
        'quiz' => [
            'title' => 'Network Infrastructure Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['Which device gives every one of its ports a separate collision domain?', 'Hub', 'Switch', 'Repeater', 'Coaxial tap', 'B'],
                ['Which device is needed to separate broadcast domains (without VLANs)?', 'Hub', 'Repeater', 'Router', 'Patch panel', 'C'],
                ['Which destination MAC address means "every device on the LAN"?', '00:00:00:00:00:00', 'FF:FF:FF:FF:FF:FF', '01:00:5E:00:00:01', 'AA:AA:AA:AA:AA:AA', 'B'],
                ['Why does one huge flat network with hundreds of hosts perform poorly?', 'Switches cannot learn MAC addresses', 'Broadcast traffic reaches every single host', 'IP addresses run out of bits', 'Cables become too long', 'B'],
                ['Which Layer 1 device regenerates a weak signal but never filters traffic?', 'Firewall', 'Repeater', 'Switch', 'Router', 'B'],
                ['How does a switch fill its MAC address table?', 'An administrator types each entry', 'It copies the table from DNS', 'It reads the source MAC address of frames it receives', 'It asks the default gateway', 'C'],
                ['A host wants to reach an IP address outside its subnet. Which MAC address does it put in the frame?', 'The destination host\'s MAC address', 'The DNS server\'s MAC address', 'The default gateway\'s MAC address', 'The broadcast address', 'C'],
                ['Which statement about a hub is true?', 'Each port is its own collision domain', 'It forwards frames using a MAC table', 'All attached devices share one collision domain and one broadcast domain', 'It routes between subnets', 'C'],
                ['Which TCP/IP layer would you check if packets are reaching the wrong network?', 'Application', 'Transport', 'Internet', 'Network Access', 'C'],
                ['What is the purpose of the TCP three-way handshake?', 'To encrypt the session', 'To establish a reliable connection before data flows', 'To assign an IP address', 'To resolve a domain name', 'B'],
                ['Which segment does a server send in reply to a SYN?', 'ACK only', 'FIN', 'SYN-ACK', 'RST', 'C'],
                ['Which protocol carries messages such as "destination unreachable" and is used by ping?', 'ICMP', 'ARP', 'DHCP', 'SMTP', 'A'],
                ['Which TCP/IP layer corresponds to OSI Layers 1 and 2?', 'Internet', 'Transport', 'Application', 'Network Access', 'D'],
                ['What does a port number identify?', 'A physical switch interface only', 'The application or service on a host', 'The IP network of a host', 'The type of cable in use', 'B'],
                ['What does IP provide at the Internet layer?', 'Addressing and best-effort routing of packets', 'Guaranteed delivery with acknowledgements', 'Encryption of the payload', 'Name resolution', 'A'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Collision or Broadcast?',
                'objective' => 'Sort devices by the domain they split and follow a broadcast across a switch.',
                'description' => 'The office network is crawling. Work out which devices split collision domains, which split broadcast domains, and what a broadcast frame really does.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'classification',
                        'title' => 'Which domain does each item separate?',
                        'instruction' => 'Place each device or feature in the category that describes what it separates.',
                        'categories' => ['Separates broadcast domains', 'Separates collision domains only', 'Separates neither'],
                        'items' => [['Router interface', 0], ['VLAN', 0], ['Switch port', 1], ['Bridge port', 1], ['Hub', 2], ['Repeater', 2]],
                        'hint' => 'Routers and VLANs stop broadcasts; switch ports stop collisions; hubs and repeaters stop nothing.',
                        'explanation' => 'Routers and VLANs split broadcast domains. Each switch or bridge port is its own collision domain. Hubs and repeaters just copy signals, so everything stays in one domain.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'A broadcast frame reaches a switch',
                        'instruction' => 'What does a switch do with a frame addressed to FF:FF:FF:FF:FF:FF?',
                        'options' => ['Drops it', 'Forwards it out every port except the one it arrived on', 'Sends it only to the router', 'Returns it to the sender'],
                        'correct' => 1,
                        'hint' => 'A broadcast is meant for everyone on the LAN.',
                        'explanation' => 'Switches flood broadcast frames out all ports except the ingress port, which is why a broadcast domain ends only at a router or VLAN boundary.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Match the infrastructure term to its meaning',
                        'instruction' => 'Connect each term to its definition.',
                        'pairs' => [
                            ['Collision domain', 'Segment where simultaneous frames would collide'],
                            ['Broadcast domain', 'Devices that receive a Layer 2 broadcast'],
                            ['MAC address', '48-bit hardware address on a network card'],
                            ['Default gateway', 'Router address used to leave the subnet'],
                        ],
                        'hint' => 'Domains describe who is affected; the other two describe addresses.',
                        'explanation' => 'Collision domains are about shared media, broadcast domains about Layer 2 reach, MAC addresses identify NICs on the local link, and the default gateway is the exit from a subnet.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Follow the Frame',
                'objective' => 'Trace a frame through a switch and place devices at the right layer.',
                'description' => 'A frame has gone missing between two offices. Follow it through the switch and rebuild the layer picture.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'What a switch does with an incoming frame',
                        'instruction' => 'Order the steps a switch follows when a frame arrives.',
                        'steps' => [
                            'The frame arrives on a port',
                            'The switch records the source MAC against that port',
                            'The switch looks up the destination MAC',
                            'The frame is forwarded out the matching port',
                        ],
                        'hint' => 'It learns first, then decides.',
                        'explanation' => 'Learn the source, look up the destination, then forward, flood or filter.',
                    ],
                    [
                        'type' => 'drag_and_drop',
                        'title' => 'Drop each device on the layer it works at',
                        'instruction' => 'Drag each device onto the OSI layer where it makes its decisions.',
                        'template_key' => 'device_to_osi_layer',
                        'pairs' => [
                            ['Hub', 'Layer 1: repeats bits'],
                            ['Switch', 'Layer 2: forwards by MAC'],
                            ['Router', 'Layer 3: routes by IP'],
                        ],
                        'hint' => 'Hubs ignore addresses; switches read MACs; routers read IPs.',
                        'explanation' => 'Hubs work on signals (Layer 1), switches on MAC addresses (Layer 2) and routers on IP addresses (Layer 3).',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Tame the broadcast storm',
                        'instruction' => 'A flat network of 200 PCs is slow because of constant broadcasts. What is the best fix?',
                        'choices' => [
                            'Replace the cables with Cat6a',
                            'Split it into VLANs or subnets joined by a router',
                            'Add more hubs',
                            'Lengthen the DHCP lease time',
                        ],
                        'correct' => 1,
                        'hint' => 'The problem is how far broadcasts travel, not the speed of the cable.',
                        'explanation' => 'Smaller broadcast domains keep chatter local. Faster cables or more hubs do not change who hears each broadcast.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: TCP/IP Under the Hood',
                'objective' => 'Map protocols to TCP/IP layers and watch a TCP session start.',
                'description' => 'A new colleague only knows the OSI model. Show how the same traffic looks in TCP/IP, and how TCP opens a connection.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 200,
                'puzzles' => [
                    [
                        'type' => 'matching',
                        'title' => 'Match each TCP/IP layer to its protocols',
                        'instruction' => 'Connect each TCP/IP layer to the protocols that live there.',
                        'pairs' => [
                            ['Application', 'HTTP, DNS, SMTP'],
                            ['Transport', 'TCP and UDP'],
                            ['Internet', 'IP and ICMP'],
                            ['Network Access', 'Ethernet and Wi-Fi'],
                        ],
                        'hint' => 'Work from the user down to the cable.',
                        'explanation' => 'Applications sit on top, TCP/UDP carry them between hosts, IP routes between networks and Ethernet/Wi-Fi move frames on the local link.',
                    ],
                    [
                        'type' => 'sequencing',
                        'title' => 'The TCP three-way handshake',
                        'instruction' => 'Put the three messages that open a TCP connection in order.',
                        'steps' => ['Client sends SYN', 'Server replies SYN-ACK', 'Client sends ACK'],
                        'hint' => 'Each side must acknowledge the other\'s starting sequence number.',
                        'explanation' => 'SYN, SYN-ACK, ACK: both sides agree on sequence numbers before any data is sent.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Speed over guarantees',
                        'instruction' => 'A live video call can tolerate a few lost packets but not delay. Which Transport protocol suits it?',
                        'options' => ['TCP', 'UDP', 'ARP', 'FTP'],
                        'correct' => 1,
                        'hint' => 'Retransmitting a late packet would only make the picture worse.',
                        'explanation' => 'UDP has no handshake or retransmission, so it adds little delay. Real-time media prefers that to TCP\'s reliability.',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 7. DNS AND WEB COMMUNICATION
    // ─────────────────────────────────────────────────────────────────────
    [
        'title' => 'DNS and Web Communication',
        'order' => 7,
        'difficulty' => 'beginner',
        'minutes' => 45,
        'description' => 'Introduction to DNS, domain names, and basic web communication.',
        'objectives' => [
            'Explain how DNS turns a domain name into an IP address.',
            'Name the common DNS record types (A, AAAA, MX, CNAME, NS, TXT).',
            'Describe the roles of the recursive resolver and the authoritative server.',
            'Read the parts of a URL and recognise HTTP methods and status code families.',
            'Explain what HTTPS and TLS add, and list the steps of a page load.',
        ],
        'content' => <<<'TXT'
HOW A DNS LOOKUP WORKS
People remember names; networks route on numbers. DNS bridges the two.
1. The browser checks its own cache, then the operating system's cache.
2. Still unknown: it asks a recursive resolver (often run by the ISP or a public service).
3. The resolver asks a root server, which points to the top-level-domain (.com, .my, .org) servers.
4. The TLD servers point to the domain's authoritative name server, which holds the real answer.
5. The answer returns to the browser and is cached for as long as the record's TTL allows.

COMMON DNS RECORD TYPES
• A – a name to an IPv4 address.
• AAAA – a name to an IPv6 address.
• CNAME – an alias that points one name at another name.
• MX – the mail servers that accept email for the domain.
• NS – the authoritative name servers for the domain.
• TXT – free text, used for SPF/DKIM email checks and domain verification.
TTL (time to live) says how many seconds a record may be cached before it must be looked up again.

ANATOMY OF A URL
In https://www.example.com:443/shop?id=7 the scheme is https, the hostname is www.example.com (the domain name DNS resolves), the port is 443, the path is /shop and the query string is id=7.

HTTP METHODS AND STATUS CODES
Common methods: GET (retrieve), POST (submit data), PUT (replace), DELETE (remove). Status codes come in families: 2xx success (200 OK), 3xx redirect (301 Moved Permanently), 4xx client error (404 Not Found, 403 Forbidden), 5xx server error (500 Internal Server Error, 503 Service Unavailable).

HTTPS AND TLS
HTTP sends everything as readable text on port 80. HTTPS wraps HTTP in TLS on port 443: the server proves its identity with a certificate and both sides agree encryption keys, so eavesdroppers cannot read or change the data. The browser padlock means the connection is encrypted, not that the site is trustworthy.

WHAT HAPPENS WHEN YOU OPEN A WEBSITE
DNS lookup → TCP connection → TLS handshake → HTTP request → HTTP response → the browser renders the page, fetching images, scripts and styles with more requests. A content delivery network (CDN) keeps copies of those files on servers close to the user to cut delay.
TXT,
        'quiz' => [
            'title' => 'DNS and Web Communication Quiz',
            'passing_mark' => 70,
            'questions' => [
                ['Which DNS record type maps a domain name to an IPv4 address?', 'MX', 'A', 'CNAME', 'TXT', 'B'],
                ['Which DNS record tells the world which servers accept email for a domain?', 'NS', 'AAAA', 'MX', 'A', 'C'],
                ['What does a CNAME record do?', 'Points a name at an IPv6 address', 'Lists the mail servers', 'Makes one name an alias of another name', 'Stores the zone\'s start of authority', 'C'],
                ['Which server holds the final, official answer for a domain?', 'Root server', 'Recursive resolver', 'Browser cache', 'Authoritative name server', 'D'],
                ['What does a recursive resolver do?', 'Hosts the website files', 'Performs the lookup steps on behalf of the client', 'Encrypts web traffic', 'Assigns IP addresses to hosts', 'B'],
                ['What does the TTL of a DNS record control?', 'How long the record may be cached', 'How many hops a packet may take', 'How fast the website loads', 'How many users can resolve the name', 'A'],
                ['Which HTTP status code means the requested page was not found?', '200', '301', '404', '500', 'C'],
                ['What does an HTTP 500 status code indicate?', 'The page moved permanently', 'The request was successful', 'The client sent a bad request', 'The server hit an internal error', 'D'],
                ['Which HTTP method is normally used to retrieve a web page?', 'GET', 'POST', 'DELETE', 'PUT', 'A'],
                ['Which port does plain HTTP use by default?', '22', '53', '80', '443', 'C'],
                ['What protects the data in an HTTPS connection?', 'The DNS cache', 'TLS encryption', 'The default gateway', 'The browser\'s history', 'B'],
                ['In https://www.example.com/shop?id=7, what is www.example.com?', 'The path', 'The query string', 'The scheme', 'The hostname (domain name)', 'D'],
                ['What is the main benefit of a content delivery network (CDN)?', 'It serves content from servers near the user to reduce delay', 'It replaces DNS', 'It blocks all malware', 'It assigns private IP addresses', 'A'],
                ['Which command-line tool queries a DNS server directly?', 'tracert', 'nslookup', 'ipconfig', 'netstat', 'B'],
                ['A website opens by its IP address but not by its name. What is the most likely problem?', 'DNS resolution is failing', 'The cable is unplugged', 'The switch has no MAC table', 'The firewall blocks all traffic', 'A'],
            ],
        ],
        'missions' => [
            [
                'title' => 'Mission 1: Name to Number',
                'objective' => 'Follow a DNS lookup and learn the record types.',
                'description' => 'Staff can reach the intranet by IP address but not by name. Learn how a lookup travels and which records answer it.',
                'difficulty' => 'beginner',
                'pass_mark' => 70,
                'xp_reward' => 150,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'Follow a DNS lookup',
                        'instruction' => 'Order the steps of a DNS lookup for a name that is not cached.',
                        'steps' => [
                            'The browser checks its own cache',
                            'The query goes to the recursive resolver',
                            'The resolver asks root, TLD, then authoritative servers',
                            'The IP address returns to the browser',
                        ],
                        'hint' => 'Local caches first, the Internet\'s DNS hierarchy last.',
                        'explanation' => 'Cache, resolver, then root → TLD → authoritative, and the answer travels back and is cached.',
                    ],
                    [
                        'type' => 'matching',
                        'title' => 'Match each DNS record to its job',
                        'instruction' => 'Connect each record type to what it does.',
                        'pairs' => [
                            ['A', 'Name to IPv4 address'],
                            ['AAAA', 'Name to IPv6 address'],
                            ['MX', 'Mail servers for the domain'],
                            ['CNAME', 'Alias pointing to another name'],
                        ],
                        'hint' => 'AAAA is four times the size of A: IPv6 is four times as long as IPv4.',
                        'explanation' => 'A and AAAA return addresses, MX names the mail servers and CNAME makes one name an alias of another.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Who has the final answer?',
                        'instruction' => 'Which server holds the official records for a domain?',
                        'options' => ['Root server', 'Recursive resolver', 'Authoritative name server', 'Browser cache'],
                        'correct' => 2,
                        'hint' => 'The word "authority" gives it away.',
                        'explanation' => 'The authoritative name server is the source of truth for the domain; resolvers and caches only pass on or remember its answers.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 2: Speaking HTTP',
                'objective' => 'Read status codes, choose HTTP methods and match services to ports.',
                'description' => 'The web team is debugging errors from the shop site. Decode the status codes and the protocol details.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 175,
                'puzzles' => [
                    [
                        'type' => 'classification',
                        'title' => 'Sort the HTTP status codes',
                        'instruction' => 'Place each status code in its family.',
                        'categories' => ['2xx Success', '3xx Redirect', '4xx Client error', '5xx Server error'],
                        'items' => [['200 OK', 0], ['201 Created', 0], ['301 Moved Permanently', 1], ['302 Found', 1], ['403 Forbidden', 2], ['404 Not Found', 2], ['500 Internal Server Error', 3], ['503 Service Unavailable', 3]],
                        'hint' => 'Only the first digit matters.',
                        'explanation' => '2xx succeeded, 3xx redirects, 4xx means the request was wrong, 5xx means the server failed.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'Submitting a form',
                        'instruction' => 'Which HTTP method is normally used to send form data that creates something on the server?',
                        'options' => ['GET', 'POST', 'HEAD', 'TRACE'],
                        'correct' => 1,
                        'hint' => 'GET only retrieves.',
                        'explanation' => 'POST carries data in the request body to create or submit something; GET is for retrieving.',
                    ],
                    [
                        'type' => 'drag_and_drop',
                        'title' => 'Match each service to its default port',
                        'instruction' => 'Drag each protocol to its well-known port.',
                        'template_key' => 'protocol_to_port',
                        'pairs' => [
                            ['HTTP', 'Port 80'],
                            ['HTTPS', 'Port 443'],
                            ['DNS', 'Port 53'],
                            ['SSH', 'Port 22'],
                        ],
                        'hint' => 'The secure web port is the larger of the two web ports.',
                        'explanation' => 'HTTP 80, HTTPS 443, DNS 53 and SSH 22 are the well-known ports technicians are expected to remember.',
                    ],
                ],
            ],
            [
                'title' => 'Mission 3: Trace the Page Load',
                'objective' => 'Order a full HTTPS page load and troubleshoot a name that will not resolve.',
                'description' => 'A customer says the shop will not open. Trace what should happen when a page loads and spot where it breaks.',
                'difficulty' => 'intermediate',
                'pass_mark' => 70,
                'xp_reward' => 200,
                'puzzles' => [
                    [
                        'type' => 'sequencing',
                        'title' => 'Opening https://example.com',
                        'instruction' => 'Order the steps that happen when you open a secure website.',
                        'steps' => [
                            'The browser asks DNS for the IP address',
                            'The browser opens a TCP connection',
                            'A TLS handshake sets up encryption',
                            'The browser sends the HTTP request',
                            'The server returns the page',
                        ],
                        'hint' => 'You need an address before you can connect, and encryption before you send anything private.',
                        'explanation' => 'DNS, then TCP, then TLS, then the HTTP request and response.',
                    ],
                    [
                        'type' => 'scenario',
                        'title' => 'Opens by IP, not by name',
                        'instruction' => 'You can open http://93.184.216.34 but not example.com. Which service is most likely failing?',
                        'choices' => ['DHCP', 'DNS', 'NAT', 'ARP'],
                        'correct' => 1,
                        'hint' => 'Which service turns names into addresses?',
                        'explanation' => 'If the IP address works but the name does not, the connection is fine and name resolution (DNS) is the problem.',
                    ],
                    [
                        'type' => 'identification',
                        'title' => 'What the padlock means',
                        'instruction' => 'What does the padlock icon in a browser tell you?',
                        'options' => ['The site is safe from all threats', 'The connection to the site is encrypted with TLS', 'The site is owned by a government', 'Cookies are disabled'],
                        'correct' => 1,
                        'hint' => 'It describes the connection, not the people running the site.',
                        'explanation' => 'The padlock means traffic is encrypted and the certificate matched; a malicious site can still have one.',
                    ],
                ],
            ],
        ],
    ],
];
