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

## In-depth notes and link health (30 Oct 2026)

- **Notes:** every stage's Study Notes now hold 9-12 sections (about 700-1,200 words each, up from about 250): how each technology works, step-by-step procedures, reference values (ports, subnet sizes, administrative distances, Wi-Fi bands), troubleshooting clues and exam traps. Content lives in `database/seeders/data/netlearn_deep_notes_a.php` and `_b.php`. Where a stage already had notes, they are kept as the Introduction and the new sections follow once; notes that still equal the first-draft seeded text are replaced. Extra sources checked: 6 GHz channel counts ([HPE Aruba Wi-Fi 6E design guide](https://arubanetworking.hpe.com/techdocs/aos/wifi-design-deploy/generations/wifi6e/_print/)), default administrative distances and STP port states ([Wikipedia: administrative distance](https://en.wikipedia.org/wiki/Administrative_distance)), TLS 1.3 handshake modes and QUIC ([IETF QUIC-TLS drafts](https://datatracker.ietf.org/doc/html/draft-thomson-quic-tls)). Points the searches did not cover (OFDMA details, DNSSEC, DoH, bridge ID priority) come from standard networking knowledge.
- **Link health:** see `app/Services/LinkHealthService.php`. Damage = 24 x (0.35 + 0.65 x severity) x escalation, where severity is the share of the answer that was wrong, and escalation is x1, x1.15, x1.3, x1.45 (cap) for consecutive misses in one try. Unlimited tasks stop at a safe-mode floor of 10; attempt-limited tasks show attempts left; a solve or a Mission Run restart refills it. Stored in `user_puzzle_progress.link_health` (migration `2026_10_30_100000`). Health never affects XP or stars.

## Mission health and matching numbers (30 Oct 2026)

- **One bar per mission run.** The health bar is shared by every task of a mission and carries over from task to task until the mission ends; restarting the mission refills it. It is split in three segments that are the mission's three stars: 67-100 health = 3 stars, 34-66 = 2, below = 1. The stars left when the last task is solved are the run's rating, and the best run is kept. Wrong answers do damage scaled by how wrong they were and by repeat misses on the task; the bar bottoms out at a safe-mode floor so retries stay unlimited. Hints cost XP, never health. Tables: `user_mission_progress.run_health`, `user_puzzle_progress.run_solved` (migration `2026_10_30_110000`).
- **The learning module now shows what the student actually did:** XP earned per activity (points minus a bought hint) instead of an analytics "best score", "solved on attempt N (M in total, counting replays)", hint used, mission XP as `earned / possible` (the same sum the debrief uses), the run health of a mission in progress, and quiz attempts with best and last result. The debrief shows this run's stars and the health left, with the best rating beside it. The per-puzzle star value is kept only for the Clean Run badge and is labelled "Clean solve" on the lecturer dossier.
- Missions completed before this change keep the star rating they had; a replay uses the new rule.

