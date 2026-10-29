<?php

/*
|--------------------------------------------------------------------------
| In-depth study notes, stages 2 and 7-10 (keyed by topic title)
|--------------------------------------------------------------------------
| Sections are split by the Study Notes tab on ALL-CAPS heading lines, so no body line
| may be written entirely in capitals. Figures such as default administrative distances,
| STP port states and the 6 GHz channel counts were checked against the sources listed in
| docs/NETLEARN_CONTENT_EXPANSION.md; the wording is original.
*/

return [

    'Network Infrastructure & Broadcast Domains' => <<<'TXT'
HOW INFRASTRUCTURE DEVICES SPLIT TRAFFIC
Network infrastructure devices decide how far a frame travels, who hears it and who competes for the medium. Understanding collision and broadcast domains explains most LAN performance problems and is the reason networks are divided into subnets and VLANs.

COLLISION DOMAINS
A collision domain is a segment in which two devices transmitting at the same moment would corrupt each other. Shared media such as hubs and old coaxial cable form one big collision domain and use CSMA/CD: listen before sending, detect a collision, send a jam signal and back off for a random time. Each switch port is its own collision domain, and with full duplex (separate send and receive paths) collisions cannot occur at all, which is why CSMA/CD is irrelevant on modern switched Ethernet. Duplex or speed mismatches between a port and a device cause late collisions and slow links, so both ends should auto-negotiate.

BROADCAST DOMAINS
A broadcast domain is the set of devices that receive a Layer 2 broadcast, a frame addressed to the all-ones MAC address FF:FF:FF:FF:FF:FF. Switches flood broadcasts out every port, so all ports in one VLAN are one broadcast domain. Routers do not forward Layer 2 broadcasts, so each router interface starts a new one. Broadcasts are necessary: ARP uses them to find MAC addresses, DHCP uses them to find a server, and some service discovery tools use them. Every host must process every broadcast, so a very large flat network wastes CPU time and bandwidth.

WHY BIG FLAT NETWORKS STRUGGLE
Suppose 500 hosts share one broadcast domain. Each sends a few broadcasts per second, so every host is interrupted hundreds of times each second. A switching loop makes it much worse: broadcast frames circle forever and multiply into a broadcast storm that can saturate every link. The cure is segmentation: split the network into several subnets or VLANs of perhaps a few hundred hosts or fewer and join them with a router or Layer 3 switch. Segmentation also improves security, because traffic between segments can be filtered.

HOW A SWITCH BUILDS AND USES ITS MAC TABLE
When a frame arrives, the switch learns the source MAC and port, then looks up the destination. A known destination is forwarded out one port, an unknown or broadcast destination is flooded, and a destination on the incoming port is filtered. Entries age out after about five minutes on many switches. A MAC flooding attack fills the table with fake entries so the switch behaves like a hub, which is why port security limits the number of MAC addresses per port.

ARP, THE GLUE BETWEEN LAYER 3 AND LAYER 2
A host that wants to send to an IP address on its own subnet broadcasts an ARP request, "who has this IP address?". The owner replies with its MAC address and the sender caches the answer for a few minutes. For a remote address the host does ARP for its default gateway instead. A host that cannot reach anything outside its subnet may have a wrong gateway or a stale ARP entry; arp -a shows the cache and arp -d clears it.

THE TCP/IP MODEL IN PRACTICE
The four TCP/IP layers are Application, Transport, Internet and Network Access. Application protocols include HTTP, DNS, SMTP, DHCP and SSH. The Transport layer provides TCP and UDP. The Internet layer provides IP, which gives every packet source and destination addresses and offers best-effort delivery, and ICMP, which carries error and diagnostic messages. Network Access defines how frames use Ethernet or Wi-Fi on the local link. Encapsulation and de-encapsulation move data between them.

TCP IN DETAIL
TCP is connection-oriented and reliable. A connection starts with the three-way handshake: the client sends a segment with the SYN flag and an initial sequence number; the server replies with SYN and ACK; the client sends ACK. Data is numbered, the receiver acknowledges what it gets and the sender retransmits what is not acknowledged. A sliding window lets the receiver control how much the sender may transmit before waiting (flow control), and congestion control slows the sender when the network is overloaded. A connection ends with FIN and ACK segments, or with RST to abort it. Typical TCP services are web, email, file transfer and remote login.

UDP IN DETAIL
UDP is connectionless. It has an eight-byte header with source and destination ports, length and checksum, no handshake, no acknowledgements and no retransmission. That makes it fast and light, so it suits DNS queries, DHCP, voice and video calls, streaming, online games and monitoring protocols where an occasional lost packet matters less than delay. If an application needs reliability on UDP, it must add it itself, as QUIC does.

PORTS AND SOCKETS
A port number is a 16-bit value that identifies a service on a host. Well-known ports are 0 to 1023 (HTTP 80, HTTPS 443, SSH 22, DNS 53, SMTP 25, DHCP 67 and 68), registered ports 1024 to 49151 and dynamic or ephemeral ports 49152 to 65535, which clients pick randomly as their source port. A socket is the combination of IP address and port, and a connection is identified by the source and destination sockets.

ICMP AND DIAGNOSTICS
ICMP reports problems: destination unreachable, time exceeded (used by traceroute when the TTL reaches zero) and echo request and reply (ping). It does not carry user data. Some firewalls block ICMP, so a failed ping does not always prove a host is down.

COMMON MISTAKES AND EXAM TRAPS
• A switch separates collision domains but not broadcast domains.
• Router interfaces, not ports, define broadcast domains, and VLANs split them without a router.
• The destination MAC address of a frame to a remote host is the gateway's, while the destination IP is the final host's.
• TCP is not "better" than UDP; each is chosen for the application.
• The three-way handshake is SYN, SYN-ACK, ACK, not SYN, ACK, SYN.
• ICMP is a Layer 3 protocol used by ping, not a transport protocol.
TXT,

    'DNS and Web Communication' => <<<'TXT'
WHY DNS EXISTS
Computers route packets by IP address, but people remember names. The Domain Name System is a distributed, hierarchical database that turns names such as www.example.com into IP addresses and back. Without it, every website visit would need a number, and a company could not move a server without telling the world. DNS also supports email routing, service discovery and security checks.

THE DNS HIERARCHY
A name is read right to left. The root (an implied dot at the end) has thirteen named server clusters operated by many organisations worldwide. Below it are top-level domains such as .com, .org, .net and country codes such as .my. Next come second-level domains (example.com) and then subdomains (www, mail). Each level is delegated to the administrators of that zone, who run authoritative name servers holding the real records. A fully qualified domain name (FQDN) lists every label, such as www.example.com.

HOW A LOOKUP WORKS
1. The application asks the operating system's stub resolver, which checks its local cache and the hosts file.
2. If the name is unknown, the query goes to a recursive resolver, usually run by the ISP, the organisation or a public service.
3. The recursive resolver checks its own cache. If it has no answer it starts at a root server, which refers it to the servers for the top-level domain.
4. The top-level domain servers refer it to the domain's authoritative name servers.
5. The authoritative server returns the record, the resolver caches it for the record's time to live (TTL) and returns it to the client.
The first lookup may take several round trips; later lookups are served from cache in milliseconds. Resolvers use UDP port 53 for most queries and fall back to TCP port 53 for large responses and zone transfers.

DNS RECORD TYPES
• A: name to IPv4 address.
• AAAA: name to IPv6 address.
• CNAME: alias to another name. A CNAME cannot sit at the zone apex beside other records.
• MX: mail exchangers for the domain, each with a priority number; the lowest number is tried first.
• NS: the authoritative name servers of the zone.
• SOA: start of authority, with the primary server, administrator contact, serial number and refresh timers.
• PTR: reverse lookup, address to name, stored in in-addr.arpa for IPv4.
• TXT: free text used for SPF, DKIM and DMARC email authentication and for proving domain ownership.
• SRV: the host and port of a service, used by some directory and voice services.
Only address records (A, AAAA and CNAME) can be proxied by a content delivery network; other types are always answered directly.

CACHING, TTL AND PROPAGATION
Every record carries a TTL in seconds. Resolvers keep an answer until it expires, which makes DNS fast but means changes spread gradually. Before moving a website, lower the TTL a day ahead, change the record, then raise it again. "DNS propagation" is just old cached answers expiring. A negative answer (the name does not exist) is also cached for a while.

DNS SECURITY
Plain DNS is unauthenticated and unencrypted. Cache poisoning feeds a resolver a forged answer so users reach the attacker's site. DNSSEC adds digital signatures to records so a resolver can verify that an answer is genuine and unchanged; it protects integrity, not privacy. DNS over TLS (DoT) and DNS over HTTPS (DoH) encrypt queries between the client and the resolver so others on the path cannot read or alter them. Typical DNS abuse also includes amplification attacks, in which small spoofed queries produce large responses aimed at a victim.

URLS AND THE WEB
A URL such as https://www.example.com:443/shop/items?id=7#reviews has parts: the scheme (https) says which protocol to use, the host (www.example.com) is resolved with DNS, the port (443, usually hidden) selects the service, the path (/shop/items) names a resource, the query (id=7) carries parameters and the fragment (reviews) is handled by the browser.

HTTP IN DETAIL
HTTP is a request and response protocol on TCP. A request has a method, a path, headers and sometimes a body. Common methods: GET retrieves, POST submits data, PUT replaces, PATCH changes part of a resource, DELETE removes, HEAD returns only headers and OPTIONS lists what is allowed. A response carries a status code, headers and a body. Status families: 1xx informational, 2xx success (200 OK, 201 Created, 204 No Content), 3xx redirection (301 Moved Permanently, 302 Found, 304 Not Modified), 4xx client error (400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 429 Too Many Requests) and 5xx server error (500 Internal Server Error, 502 Bad Gateway, 503 Service Unavailable, 504 Gateway Timeout). HTTP is stateless, so cookies and tokens carry a session between requests.
HTTP/1.1 reuses connections and can open several in parallel. HTTP/2 multiplexes many requests over one TCP connection and compresses headers. HTTP/3 runs over QUIC on UDP, building TLS 1.3 into its handshake and avoiding head-of-line blocking at the transport layer.

HTTPS AND TLS
HTTPS is HTTP inside a TLS session on port 443. In a TLS 1.3 handshake the client sends its supported ciphers and a key share, the server answers with its certificate and its own key share, both derive session keys, and encrypted data begins after one round trip; resumed sessions can even send data in 0-RTT mode. The certificate proves the server's identity: it names the domain, carries the server's public key and is signed by a certificate authority the browser trusts. The browser checks the name, the dates and the chain of signatures. The padlock means the connection is encrypted and the certificate matched the name; it does not prove the site is honest.

WHAT HAPPENS WHEN YOU OPEN A WEBSITE
Typing a URL triggers a DNS lookup, then a TCP connection to port 443 (the three-way handshake), then the TLS handshake, then an HTTP request. The server returns HTML, and the browser requests every image, script and stylesheet it references, sometimes from other domains. Caches, a content delivery network (CDN) that serves copies from servers close to the user, and compression reduce delay. Load balancers and reverse proxies spread requests across servers.

TROUBLESHOOTING NAMES AND WEB ACCESS
If a site opens by IP address but not by name, DNS is the problem: check the configured DNS server, try nslookup example.com or dig example.com, and test another resolver. If the name resolves but the page times out, check routing and firewalls with ping and traceroute. Certificate warnings usually mean a wrong date on the client, an expired certificate or a name mismatch. A 404 means the path is wrong, a 403 means access is denied, and a 5xx points to the server.

COMMON MISTAKES AND EXAM TRAPS
• DNS maps names to addresses; it does not transfer web pages.
• CNAME is an alias, A is an address, MX is for mail, NS names the zone's servers.
• TTL controls caching time, not hop count (that is the IP TTL field).
• HTTPS encrypts the connection; it does not guarantee a site is trustworthy.
• 4xx errors are the client's fault, 5xx errors are the server's.
• HTTP uses port 80, HTTPS 443 and DNS 53.
TXT,

    'Routing, Switching & VLANs' => <<<'TXT'
LAYER 2 AND LAYER 3 AT A GLANCE
Switches move frames inside a network using MAC addresses; routers move packets between networks using IP addresses. Large networks combine both: many VLANs and switches at Layer 2, joined by routing at Layer 3. This stage covers how to divide a LAN with VLANs, how to keep redundant links loop-free and how routers choose paths.

VLANS IN DETAIL
A virtual LAN splits one physical switch (or a whole switched network) into separate logical Layer 2 networks. Each VLAN has an ID from 1 to 4094 and is its own broadcast domain and, normally, its own IP subnet. Typical VLANs: staff, guests, servers, voice, management and a parking VLAN for unused ports. Benefits are smaller broadcast domains, security separation, flexible design (a user moves between groups by a port setting, not a cable) and easier policy. Best practice is to avoid using VLAN 1 for user traffic and to shut down and park unused ports.
Port types: an access port belongs to one VLAN and connects an end device. A voice port can carry a second, voice VLAN for an IP phone alongside the PC's data VLAN. A trunk port carries several VLANs between switches or to a router and tags each frame.

802.1Q TAGGING AND TRUNKS
The IEEE 802.1Q standard inserts a four-byte tag into the Ethernet frame holding a 12-bit VLAN ID and a 3-bit priority value used for quality of service. Frames in the native VLAN are sent untagged on a trunk; both ends must agree on it, and mismatches cause leaks and warnings. Only the VLANs allowed on a trunk are carried, so prune the list to what is needed. Link aggregation (LACP) bundles several physical links into one logical trunk for more bandwidth and resilience.

INTER-VLAN ROUTING
Different VLANs are different subnets, so devices need a router to talk. Three designs: one physical router interface per VLAN (wasteful), router on a stick (one trunk link with a sub-interface for each VLAN, each acting as that VLAN's default gateway) and a Layer 3 switch with switched virtual interfaces (SVIs), which routes in hardware and is the usual campus design. Access control lists on the routing point decide which VLANs may reach which.

SPANNING TREE PROTOCOL
Redundant links protect against failure but create loops, and Ethernet has no TTL to stop a looping frame, so broadcasts multiply into a storm and MAC tables thrash. Spanning Tree Protocol (IEEE 802.1D) fixes this by logically blocking redundant paths.
1. Switches exchange bridge protocol data units (BPDUs). Each switch has a bridge ID made from a priority (default 32768) and its MAC address.
2. The switch with the lowest bridge ID becomes the root bridge. Administrators lower the priority of the switch that should be root, normally a core switch, so the choice is deliberate.
3. Every other switch picks the root port, its lowest-cost path to the root. On each link one designated port forwards towards the root.
4. All remaining ports are blocked to break loops.
Classic 802.1D ports move through Blocking, Listening, Learning and Forwarding, which can take 30 to 50 seconds. Rapid STP (802.1w) converges in a second or two using roles and a handshake, and is the modern default. PortFast lets an end-device port skip the delay, and BPDU guard shuts it down if a switch is plugged in by mistake. Per-VLAN versions run a separate tree for each VLAN.

HOW A ROUTER FORWARDS A PACKET
The router receives a frame, removes the Layer 2 header, reads the destination IP address, searches its routing table and chooses the route with the longest matching prefix. It reduces the TTL by one (dropping the packet and sending an ICMP time-exceeded message if it reaches zero), then builds a new frame for the next hop: its source MAC is the outgoing interface and its destination MAC is found by ARP. The IP addresses stay the same end to end; the MAC addresses change at every hop.

READING A ROUTING TABLE
Each entry lists the destination prefix, the next hop address or exit interface, the source of the route (connected, static or the protocol), the metric and the administrative distance. A route to a directly connected network appears as soon as an interface is up with an address. The most specific route wins: for destination 10.1.1.5, a route for 10.1.1.0/24 beats one for 10.1.0.0/16 and both beat a default route. The default route 0.0.0.0/0 handles everything else and normally points at the ISP. If nothing matches and no default exists, the packet is dropped.

STATIC AND DYNAMIC ROUTING
Static routes are typed by hand. They are simple, predictable, cost no bandwidth and suit small or stub networks, but do not adapt to failure. A floating static route has a higher administrative distance, so it sits unused until the preferred route disappears and acts as a backup. Dynamic routing protocols let routers exchange information and adapt.
• RIP: distance vector, metric is hop count with a maximum of 15, simple but slow to converge.
• OSPF: link-state, builds a map of the network in areas and runs the shortest path first algorithm using cost based on bandwidth; fast, scalable and standard inside organisations.
• EIGRP: an advanced distance-vector protocol developed by Cisco.
• BGP: path-vector protocol that exchanges routes between organisations and runs the Internet; policy matters more than speed.
Interior gateway protocols (RIP, OSPF, EIGRP) work inside one organisation; BGP is the exterior gateway protocol between autonomous systems.

METRICS AND ADMINISTRATIVE DISTANCE
When one protocol offers several routes to the same prefix, the lowest metric wins. When different sources offer the same prefix, the lowest administrative distance (trustworthiness of the source) wins. Common defaults: connected 0, static 1, external BGP 20, OSPF 110, RIP 120 and internal BGP 200. Therefore a static route beats OSPF, and OSPF beats RIP.

FIRST-HOP REDUNDANCY
Hosts depend on their default gateway, so a single router is a single point of failure. First-hop redundancy protocols such as HSRP, VRRP and GLBP let two routers share a virtual gateway address, and the standby takes over if the active router fails.

VERIFYING AND TROUBLESHOOTING
Useful checks: show the routing table, interface status, VLAN list and trunk list; ping the gateway, then a remote host; use traceroute to find where the path stops. Frequent faults: a port in the wrong VLAN, a VLAN missing from a trunk, a native VLAN mismatch, no default route, a wrong gateway on hosts, a loop caused by a duplicated cable when STP is disabled and overlapping subnets.

COMMON MISTAKES AND EXAM TRAPS
• VLANs create separate broadcast domains but still need routing to talk to each other.
• 802.1Q is the tagging standard; 802.1D is Spanning Tree; 802.1X is port authentication.
• The longest prefix wins over administrative distance when choosing among routes with different prefix lengths; distance only compares routes to the same prefix.
• Lower administrative distance and lower metric are better.
• The root bridge is chosen by the lowest bridge ID, not the fastest switch.
• RIP's maximum is 15 hops; 16 means unreachable.
TXT,

    'Wireless Networking & Wi-Fi' => <<<'TXT'
HOW WIRELESS NETWORKS WORK
A wireless LAN sends data as radio waves between clients and access points. Radio is a shared, half-duplex medium: only one device transmits on a channel at a time. Wi-Fi therefore uses CSMA/CA: a device listens, waits a random back-off and only then sends, and the receiver acknowledges each frame. The IEEE 802.11 family defines the Layer 1 and Layer 2 behaviour, while IP above it is the same as on a cabled network. The access point bridges the wireless and wired sides, so a Wi-Fi problem is often a Layer 1 or Layer 2 problem.

WI-FI GENERATIONS
• 802.11b (2.4 GHz, 11 Mbps) and 802.11g (2.4 GHz, 54 Mbps) are obsolete.
• 802.11a (5 GHz, 54 Mbps) was the first 5 GHz standard.
• 802.11n, Wi-Fi 4 (2.4 and 5 GHz): introduced MIMO, using several antennas and spatial streams at once.
• 802.11ac, Wi-Fi 5 (5 GHz only): wider channels up to 160 MHz, higher modulation and multi-user MIMO on the downlink.
• 802.11ax, Wi-Fi 6 (2.4 and 5 GHz) and Wi-Fi 6E (adds 6 GHz): OFDMA splits a channel into small resource units so one transmission serves several clients, better multi-user MIMO in both directions, target wake time to save battery and 1024-QAM modulation.
• 802.11be, Wi-Fi 7: 320 MHz channels in the 6 GHz band, 4096-QAM and multi-link operation so a device uses several bands at once.
Newer generations are backward compatible, but older clients on a network can reduce its efficiency, because they take longer to transmit.

FREQUENCY BANDS AND CHANNELS
• 2.4 GHz: longest range and best wall penetration but slow and crowded. In the 20 MHz channel plan there are only three channels that do not overlap, 1, 6 and 11 (channels 1 to 13 are available in many regions). Bluetooth, microwave ovens, cordless phones and neighbouring networks interfere.
• 5 GHz: far more channels, faster, shorter range. Some channels are shared with weather and military radar and require dynamic frequency selection (DFS): the AP must leave if radar is detected.
• 6 GHz (Wi-Fi 6E and 7): about 1200 MHz of spectrum in the United States, which fits 59 channels of 20 MHz, 29 of 40 MHz, 14 of 80 MHz and 7 of 160 MHz. It is clean and uncrowded because only the newest devices can use it, with shorter range and security by WPA3 only. Clients scan only a small set of preferred scanning channels to find networks quickly.
Channel width trades speed for capacity: 20, 40, 80 or 160 MHz. Wider channels are faster for one client but leave fewer non-overlapping channels, so dense deployments use 20 or 40 MHz and carefully planned channel reuse. Co-channel interference (two APs on the same channel hear each other and share airtime) is usually less harmful than adjacent-channel interference (overlapping channels corrupt each other).

SIGNAL, NOISE AND SPEED
Signal strength is measured in dBm, a negative number: around minus 50 is excellent, minus 67 is good enough for voice and video, minus 70 is workable and below minus 80 is poor. The signal-to-noise ratio matters more than raw strength, because a strong signal in a noisy room is still slow. Distance, walls (concrete, metal and water in pipes or fish tanks absorb most), reflections and interference reduce speed. Modulation and coding adapt automatically: far clients drop to slower rates, and the slowest client can consume the most airtime.

WIRELESS COMPONENTS AND ARCHITECTURES
• SSID: the network name, broadcast in beacons. A BSSID is the MAC address of one AP radio. An extended service set uses several APs with the same SSID, so clients can roam.
• Access point: bridges wireless to wired. Autonomous APs are configured individually; lightweight APs are managed by a WLAN controller (on premises, virtual or cloud), which sets channels and power, balances clients, pushes policy and firmware and coordinates fast roaming.
• Antennas: omnidirectional antennas radiate in all horizontal directions; directional antennas (patch, Yagi, parabolic) focus the signal for long links or corridors.
• Power over Ethernet powers ceiling-mounted APs from the switch.
• Modes: infrastructure (clients use an AP), ad hoc (client to client) and mesh (APs relay traffic wirelessly to each other).
Roaming should be seamless: clients decide when to move, so overlap of about 15 to 20 per cent between cells and standards such as 802.11k, 802.11r and 802.11v help.

OTHER WIRELESS TECHNOLOGIES
Bluetooth: short-range personal area networking at 2.4 GHz. NFC: a few centimetres, for payments and pairing. Zigbee and Z-Wave: low-power sensors for homes. Cellular: 4G LTE and 5G for wide areas; 5G adds very high speed, low latency and massive device counts. Satellite: wide coverage with higher delay, though low-orbit systems reduce it. Point-to-point microwave links join buildings without cable.

SECURING WIRELESS NETWORKS
Open networks send everything in clear. WEP (RC4) is broken in minutes and must not be used. WPA improved it with TKIP; WPA2 uses AES with CCMP; WPA3 adds Simultaneous Authentication of Equals, which resists offline password guessing, forward secrecy and protected management frames, and has an Enhanced Open mode that encrypts public hotspots. Personal modes use a shared passphrase; Enterprise modes use 802.1X with a RADIUS server so each user has their own credentials. Best practices: WPA3 or WPA2-AES, long unique passphrases, change default admin passwords, update firmware, disable WPS, separate guest and staff networks with VLANs and client isolation, and watch for rogue access points. Hiding the SSID and MAC filtering stop only the careless. An evil twin copies a real SSID; a captive portal can confuse users, so verify the network name.

WIRELESS SITE SURVEYS AND DESIGN
A predictive survey uses floor plans and building materials to plan AP positions; an on-site survey measures real signal and noise with a laptop or analyser; a post-deployment survey verifies. Design for capacity, not just coverage: count clients, expected applications and the number of APs, keep cell sizes small in busy areas, and place APs on non-overlapping channels with appropriate transmit power.

COMMON WIRELESS PROBLEMS
• Dead spots: add or move an AP, or use a mesh node.
• Slow speed despite full bars: interference, a crowded channel or an old client taking airtime.
• Intermittent drops: roaming problems, DFS radar events or power-saving settings.
• Cannot connect: wrong passphrase, wrong security mode, MAC filtering or a full DHCP pool.
• Connected but no Internet: DHCP, DNS or the wired uplink, not the radio.
Tools: a Wi-Fi analyser app, spectrum analyser, ping and speed tests at several positions.

COMMON MISTAKES AND EXAM TRAPS
• Only channels 1, 6 and 11 are non-overlapping in the 2.4 GHz band.
• 5 GHz is faster but has shorter range; 2.4 GHz has longer range.
• 802.11ax is Wi-Fi 6 and 802.11ac is Wi-Fi 5; Wi-Fi 6E adds the 6 GHz band.
• WPA3 is the current best; WEP must never be used.
• Hiding the SSID is not security.
• An AP is not a router, and a WLAN controller manages APs.
TXT,

    'Network Services & Troubleshooting' => <<<'TXT'
SERVICES THAT MAKE NETWORKS USABLE
A network is more than cables and routers. Services such as DHCP, DNS, NAT, time synchronisation, monitoring and logging make it usable and manageable. Troubleshooting is the skill of finding and fixing faults in all of them.

DHCP IN DETAIL
Dynamic Host Configuration Protocol gives hosts an address, subnet mask, default gateway, DNS servers and other options automatically from a pool.
1. Discover: the client broadcasts from 0.0.0.0 to 255.255.255.255 (UDP port 68 to 67) looking for servers.
2. Offer: a server proposes an address and options.
3. Request: the client broadcasts which offer it accepts, so other servers can withdraw theirs.
4. Acknowledge: the server confirms the lease.
Remember it as DORA. The lease lasts for a set time, such as eight hours or a day. The client tries to renew at half the lease time (T1) and rebinds with any server at 87.5 per cent (T2). If no server answers, the host gives itself a 169.254.x.x link-local address (APIPA), which is a sign DHCP is unreachable. Use reservations to give a device a fixed address by its MAC, exclusions for addresses you manage by hand, and a DHCP relay (an IP helper address on the router) to forward broadcasts to a server on another subnet. A rogue DHCP server hands out wrong gateways; DHCP snooping on switches only trusts server replies from approved ports. A full pool and short leases are common causes of "no address" faults.

NAT AND PAT IN DETAIL
Private addresses cannot be routed on the Internet, so the edge router uses Network Address Translation. Static NAT maps one private address to one public address (to publish a server). Dynamic NAT takes addresses from a pool. Port Address Translation, also called NAT overload, maps many private hosts to one public address by giving every conversation a unique source port, and the router stores the mapping in a translation table. Terms: inside local (the private address), inside global (its public translation), outside global (the remote host). NAT hides internal addressing and conserves public IPv4 addresses, but it breaks some protocols, complicates end-to-end connections and is not a firewall. IPv6 has enough addresses that NAT is not needed.

ICMP, PING AND TRACEROUTE
ICMP carries control and error messages inside IP packets. Ping sends echo requests and measures round-trip time and loss. A sequence for connectivity testing is: ping 127.0.0.1 (the local stack), ping your own address (the NIC), ping the default gateway (the local link), ping a remote IP address such as 8.8.8.8 (routing) and ping a name (DNS). The first failing step shows where to look. Traceroute (tracert on Windows) sends packets with increasing TTL values; each router that drops one returns "time exceeded", revealing the path and the delay at each hop. Asterisks mean no reply, which may only mean a firewall blocks ICMP. "Destination unreachable" comes from a router that has no route or a firewall that rejects. "Request timed out" means no reply returned at all.

ARP AND NEIGHBOUR TABLES
ARP maps an IPv4 address to a MAC address on the local network. Hosts keep a cache for minutes. ARP has no authentication, so ARP spoofing can redirect traffic; dynamic ARP inspection on switches checks replies against DHCP records. In IPv6 the Neighbor Discovery protocol does the same job.

NTP, SYSLOG AND SNMP
• NTP keeps clocks synchronised over UDP port 123. Accurate time matters for log correlation, certificates and authentication tickets.
• Syslog sends log messages, each with a facility and a severity from 0 (emergency) to 7 (debug), to a central server over UDP port 514 so events from many devices can be searched together.
• SNMP lets a management station poll agents on devices for variables defined in a management information base (MIB), and lets devices send traps when something happens. Versions 1 and 2c send a community string in clear text; SNMPv3 adds authentication and encryption. Ports are 161 for polling and 162 for traps.
• NetFlow and similar flow records show who is using bandwidth and which applications run.

MONITORING AND DOCUMENTATION
Good operations keep a baseline of normal behaviour (utilisation, latency, error counters), so faults stand out. Monitor interface errors and discards, CPU and memory, link utilisation, device temperature and uptime. Alerts have thresholds and escalation. Documentation includes a network diagram, an IP address plan, cable and port labels, configuration backups and change records. Most outages follow a recent change, so a change log is the first place to look.

THE TROUBLESHOOTING METHOD
1. Identify the problem: gather information, question users, reproduce the fault, find out what changed and check whether others are affected.
2. Establish a theory of probable cause, starting with the simplest and most likely, and consider multiple approaches such as top-down or bottom-up.
3. Test the theory to determine the cause. If it fails, form a new theory or escalate.
4. Establish a plan of action, including possible effects, and implement the solution, or escalate.
5. Verify full system functionality and implement preventive measures.
6. Document findings, actions and outcomes.
Change one thing at a time, keep notes, and have a way back.

COMMANDS FOR EVERYDAY TROUBLESHOOTING
• ipconfig /all (Windows) or ip addr and ip route (Linux): local address, mask, gateway, DNS, MAC and DHCP lease.
• ipconfig /release and /renew: ask for a new DHCP lease. ipconfig /flushdns clears the resolver cache.
• ping and tracert (traceroute on Linux and macOS): reachability and path.
• nslookup and dig: query DNS servers directly.
• arp -a: show the ARP cache.
• netstat -an or ss: open connections and listening ports.
• pathping and mtr: combine ping and traceroute statistics.
• A packet analyser such as Wireshark: shows exactly what is on the wire, with filters for protocols and hosts.

COMMON FAULTS AND CLUES
• Link light off: bad cable, dead port, disabled interface or wrong speed settings.
• 169.254.x.x address: no DHCP reply; check the cable, the VLAN and the server or relay.
• Can reach local hosts but not other subnets: wrong subnet mask or default gateway, or a missing route.
• Can ping an IP address but not a name: DNS server wrong or down.
• Intermittent connectivity: duplicate IP addresses, a loop, a failing cable or duplex mismatch.
• Slow network: broadcast storm, saturated uplink, interference on Wi-Fi or an overloaded device.
• One user cannot reach one site: proxy settings, a firewall rule, a stale DNS cache or a hosts file entry.

COMMON MISTAKES AND EXAM TRAPS
• DHCP is DORA: Discover, Offer, Request, Acknowledge, in that order.
• PAT uses port numbers to share one public address; NAT alone may map one to one.
• A failed ping does not always mean the host is down, because ICMP may be blocked.
• Identify the problem first; do not start swapping equipment.
• Always verify the fix and document it.
• SNMPv3 is the secure version; community strings in v1 and v2c are clear text.
TXT,

];
