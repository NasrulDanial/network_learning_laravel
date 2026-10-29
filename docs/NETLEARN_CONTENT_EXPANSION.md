# NetLearn content expansion: 10 stages, 15-question quizzes, 20 badges

Seeders added on branch `fix/post-testing`. Run with `php artisan db:seed` (idempotent). Back up the database first.

## What the seeders produce

| | Before | After |
|---|---|---|
| Stages | 5 seeded (+2 older ones in the dev DB) | 10 |
| Questions per quiz | 10 (some 5–7 in older stages) | 15 |
| Missions per stage | 3 seeded | 3 seeded (stages that already had an older "Core Lab" mission keep it, so they show 4) |
| Puzzles | 45 | 90 |
| Badges | 10 | 20 |

Stages, in order:
1. OSI Reference Model & PDU Encapsulation
2. Network Infrastructure & Broadcast Domains (existing in the dev DB, topped up)
3. Network Devices
4. IP Addressing
5. Network Topology
6. Network Security Basics
7. DNS and Web Communication (existing in the dev DB, topped up)
8. **Routing, Switching & VLANs** (new)
9. **Wireless Networking & Wi-Fi** (new)
10. **Network Services & Troubleshooting** (new: DHCP, NAT, ICMP, ARP, SNMP, syslog, troubleshooting method)

## How existing data is treated

- Records are matched by title/text and only created when missing; lecturer edits and student history are never overwritten.
- A quiz is **topped up to 15** with questions it does not already have, never beyond 15.
- Stages 2 and 7 keep their existing notes; the new note sections are added after them, so the Study Notes tab can split them into sections.
- Six plain badge names are renamed in place (same row), so badges students already earned stay earned.

## Badges (20)

First Ping!, Stage Clear!, OSI Cartographer, Side Quest Slayer, Halfway Through the Stack, Boss Rush Champion, Fundamentals Unlocked, Network Legend, Clearance Level 2: Packet Analyst, Loot Goblin, Clearance Level 5: Principal Network Architect, Hoard of Bytes, Quiz Whiz, Perfect Handshake, Flawless Victory, Triple Star, Star Collector, Puzzle Hoarder, Big Brain Energy, Clean Run.

Renamed: Packet Tracer Initiate → First Ping!, Topic Finisher → Stage Clear!, Mission Specialist → Side Quest Slayer, XP Collector → Loot Goblin, Mission Master → Boss Rush Champion, Network Fundamentals Graduate → Fundamentals Unlocked.

`AchievementService` gained five requirement types (`quizzes_passed`, `perfect_quizzes`, `three_star_missions`, `puzzles_solved`, `three_star_puzzles`); the achievements page shows progress for all of them.

## Research and sources

The scope and ordering follow published course outlines; all notes, questions and puzzles are original wording.

- CompTIA Network+ N10-009 objectives, five domains (Networking Concepts, Network Implementation, Network Operations, Network Security, Network Troubleshooting): [DestCert N10-009 study guide](https://destcert.com/resources/comptia-network-n10-009/), [N10-009 objectives explained](https://destcert.com/resources/n10-009-objectives/).
- Cisco CCNA / Networking Academy "Introduction to Networks" topics (DHCP, NAT, ICMP, wireless): [CCNAv7 outline](https://ace.ucv.ro/pdf/stiri/2022/CCNAv7-outline.pdf), [Cisco 200-301 exam topics](https://learningcontent.cisco.com/documents/marketing/exam-topics/200-301_CCNA_v2.0_Exam_Topics_PDF.pdf).
- DNS record types: [Cloudflare DNS record types reference](https://developers.cloudflare.com/dns/manage-dns-records/reference/dns-record-types/index.md).

The web searches returned mostly third-party summaries, so details such as VLAN and troubleshooting steps are written from standard networking knowledge, not copied from any one page. A lecturer should review the content before it is used for grading.

## Files

- `database/seeders/data/netlearn_expansion_*.php`: the new content (extra questions, stages 2 and 7, stages 8–10).
- `database/seeders/Support/CurriculumData.php`: assembles the whole curriculum.
- `database/seeders/CurriculumSeeder.php`, `AchievementSeeder.php`: top-up and rename logic.
- Tests: `CurriculumSeederTest`, `CurriculumTopUpTest`, `AchievementSeederTest`.
