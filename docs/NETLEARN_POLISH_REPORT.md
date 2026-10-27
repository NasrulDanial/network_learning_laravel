# NetLearn Polish & Repair Report — 27 Oct 2026

Scope: the six review/blueprint documents (UX Implementation Prompt v2, UX & Module Improvement
Blueprint, Current Project Review, Repair Prompt, Project Audit, Master Prompt), checked against the
code on `feature/puzzle-based-learning`. Each finding was verified before acting; several were
already fixed in the code and are listed as such.

## How this was verified

| Check | Result | Notes |
|---|---|---|
| Automated tests (`php artisan test`) | **PASS — 94 tests, 336 assertions** | Now run against the isolated `network_learning_laravel_test` database. `tests/TestCase.php` refuses to run on any database not ending in `_test` and rolls back every test. |
| Fresh install (`migrate` on an empty DB) | **PASS** | The audit's `completion_percentage` migration concern no longer applies. |
| Seeding twice | **PASS** | No duplicates; a lecturer-edited topic title survives re-seeding. |
| Migration up/down (new unique index) | **PASS** | Tested on the test DB; applied to the dev DB after a full backup. |
| Link crawl, every page (student 12, lecturer 51) | **PASS** | No errors, no PHP error text; locked topic correctly redirects. |
| Inline handler audit (29 lecturer page types) | **PASS** | Every `onclick`/`oninput`/`onsubmit` calls a defined function; no unbound buttons. |
| WCAG contrast audit (31 page views × light and dark) | **PASS — 0 failures** | Automated text-vs-background check (4.5:1 normal, 3:1 large). Text over gradients/images is skipped by the checker and was reviewed by screenshot only. |
| Phone (375px) and tablet (768px) layout audit | **PASS** | Strict check: nothing clipped by `overflow:hidden` or off-screen on any student or lecturer page. |
| Mobile drawer (both roles) | **PASS** | Opens; closes via button, ✕ arrow, backdrop, nav link, Escape; focus moves in and back; scroll locked while open. |
| Desktop sidebar collapse | **PASS** | Toggles, label flips, persists to next page, active item correct. |
| Rank dialog | **PASS** | Keyboard open, focus in, Tab trapped, Escape closes, focus returns. |
| Puzzle submit pipeline (incomplete → wrong → right) | **PASS** | Run through `PuzzleUtils.submitResponse`, the same code the Phaser buttons use. |
| Lecturer inline preview | **PASS** | Opens in a drawer on the mission page, posts to the preview endpoint (no attempts/XP). |
| Phaser canvas drag/drop and visual play | **NOT TESTED** | The browser pane was hidden, so canvases did not render frames. Submit/retry logic was verified at the JavaScript layer instead. |
| Real student/lecturer data in a browser | **NOT TESTED by design** | Browser testing used the test database so no progress rows were written to your data. |

## Defect register

Severity: P0 security/data integrity · P1 broken workflow · P2 consistency/polish.

| ID | Sev | Defect (cause) | Fix | Verified by |
|---|---|---|---|---|
| D1 | P0 | Feature tests wrote to the dev database (config was cached; no isolation). Caused unsolvable puzzles in Mission 1. | Dedicated `_test` DB, guard in `TestCase`, transactions; config cache cleared. | Row counts unchanged after full run |
| D2 | P0 | Deleting a topic/mission/activity/quiz cascaded away student attempts, results and progress. | Delete refused when history exists; message suggests Draft. | `ProgressionIntegrityTest`, `QuizManagementTest` |
| D3 | P0 | Students could play puzzles that belong to no mission by URL (and earn XP). | Access requires a published, unlocked mission. | `test_standalone_puzzle_without_mission_cannot_be_played` |
| D4 | P0 | Same badge could be inserted twice under concurrent submissions. | Unique `(user_id, achievement_id)` index (migration with duplicate pre-check). | `test_badge_cannot_be_recorded_twice_for_same_student` |
| D5 | P0 | Preview drawer built its iframe by pasting lecturer text into HTML. | DOM APIs; success banner escapes server values. | Code review + drawer test |
| D6 | P1 | Mobile menu never opened: two scripts toggled the same drawer. | Single implementation, bound once. | Browser test (both roles) |
| D7 | P1 | Quiz "Steps" inserted placeholder questions; "Design Board" saved unsolvable puzzles; "Settings" mixed fake fields. | Replaced by a quiz library: Edit Questions / Settings / Delete; endpoints removed. | `QuizManagementTest` |
| D8 | P1 | Learning Journey and My Progress always showed 0 missions (`select()` discarded `withCount`). | Count added after `select()`. | `test_journey_and_progress_pages_report_published_mission_counts` |
| D9 | P1 | Light mode: quiz questions, stat numbers, headings and table headers were white-on-white. | Light-theme remapping of inline colours + token fixes. | Contrast audit |
| D10 | P1 | Phone layouts: sidebar sliver, clipped header, dashboards and dossier overflowing. | Responsive rules; drawer fully hidden; compact header. | Layout audit + screenshots |
| D11 | P1 | Submit error replaced the server's reason with jargon ("Packet transmission failed"). | Server message shown in plain language. | Browser submit test |
| D12 | P1 | Demo seeder overwrote lecturer edits to topics 1–2, their missions and puzzles. | `firstOrCreate` (create only if missing). | Re-seed test |
| D13 | P2 | Lecturer dashboard: "0%" pass rate/average with no attempts; blank charts; activity feed ignored puzzle attempts while the total counted them. | "No quiz attempts yet", chart empty states, unified quiz+puzzle feed, theme-aware chart colours. | Screenshots |
| D14 | P2 | Duplicate topic/mission/activity positions accepted silently. | Unique-position validation with clear messages. | `test_duplicate_positions_are_rejected` |
| D15 | P2 | Missions with attempts could be moved to another topic, splitting history. | Blocked with an explanation. | `test_mission_with_attempts_cannot_move_to_another_topic` |
| D16 | P2 | "Practice Arena" described required topic quizzes as optional with wrong XP rules. | Renamed "Topic Quizzes"; copy states the real pass/XP rules. | Page text |
| D17 | P2 | Closed modals stayed focusable/announced; rank dialog had no focus handling. | Hidden when closed; focus management. | Browser test |
| D18 | P2 | "Not started" badge styled red like "Failed"; result banner said "Mission cleared" for one activity and "+0 XP" on repeats. | Distinct neutral state; accurate banner text. | Screenshots / submit test |
| D19 | P2 | Chart.js loaded as "latest" from the CDN. | Pinned to 4.4.1. | Charts render |

Fixed in an earlier commit on this branch (same day): double-counted quiz XP, draft topics blocking the
sequence, missions without required puzzles never completing, quizless topics stuck at 75%,
per-topic rank formula, misaligned puzzle-form answers.

Already fixed before this work (audit claims re-checked): forged `is_correct` fallback, retry button
lock, feedback rendered as HTML, progress migration, enum mismatch, public lecturer registration.

## Theme test matrix

| Page type | Light | Dark | Phone 375 | Tablet 768 |
|---|---|---|---|---|
| Student dashboard + rank dialog | PASS | PASS | PASS | PASS |
| Learning journey / topic / missions | PASS | PASS | PASS | PASS |
| Topic quizzes / quiz / result | PASS | PASS | PASS | PASS |
| My progress / achievements / profile | PASS | PASS | PASS | PASS |
| Puzzle player page chrome | PASS | PASS | PASS | PASS |
| Phaser canvas contents | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Lecturer dashboard (charts, feed) | PASS | PASS | PASS | PASS |
| Topics, missions, mission detail, puzzle forms | PASS | PASS | PASS | PASS |
| Quiz library, questions | PASS | PASS | PASS | PASS |
| Student performance + dossier | PASS | PASS | PASS | PASS |
| Profiles | PASS | PASS | PASS | PASS |

## Remaining issues (not done)

1. **Phaser canvas** keeps its own dark palette in light mode, and drag/drop play was not exercised visually. Check one puzzle of each type by hand.
2. **Wording**: "Operative", "Telemetry" and "Clearance" still appear in many headings; the brief suggests plainer language. Fixed only where it misled (quiz rules, result banner, errors).
3. **Topic cover image alt text** (blueprint B2) is not stored; the schema has no column for it.
4. **Student Performance filters** by mission, activity type and date range (B5) are not implemented; search, status and topic filters exist.
5. **Two seeders** still exist (`NetLearnSeeder`, `NetworkingMissionSeeder`); they are now non-destructive but not merged.
6. **Older test files** (`ComprehensiveSystemAuditTest`, `ModuleSmokeTest`, `MissionAndPuzzleTest`) still use `if (!$x) return;`, which skips silently when seed data is missing. New tests use explicit fixtures.
7. **Leftover rows in your dev DB** from earlier test runs: users `test_student_ux@netlearn.local` and `test_lecturer_ux@netlearn.local`, and standalone puzzles #2, #13, #37, #38 (now unplayable by students). Remove them only if you want to.

## Database changes on the dev database

- Backup taken first: `storage/app/backups/network_learning_laravel_2026-10-27_before_polish.sql` (git-ignored).
- Migration `2026_10_27_120000_add_unique_user_achievement_constraint` applied (no duplicates existed).
- One accidental change during this work was reverted: seeder runs re-attached puzzles #2, #13, #37, #38 to Mission 1; all four were set back to no mission.
