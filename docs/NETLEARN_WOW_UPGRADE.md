# NetLearn "Wow Factor" Upgrade — 28 Oct 2026

Implementation notes for `NetLearn_Upgrade_Development_Spec.txt`, branch `feature/wow-upgrade`.
The server is still the only judge: every new visual reacts to the server's response, and no
answer key reaches the browser before an answer is locked.

## What shipped

| Phase | What students and lecturers see | Main files |
|---|---|---|
| 0 | `config/netlearn.php` flags (`NETLEARN_RUN_MODE`, sound default, hint penalty, free hint after N fails, TTL, diagnostics level), mirrored in `.env.example` | `config/netlearn.php` |
| 1 | NetFX: synthesised sounds, shake, flash, vibrate, typed text, count-up; header sound toggle synced across frames; link health pill; paid hint (free after 2 fails or once solved) | `public/js/run/netfx.js`, `public/js/puzzles/puzzleUtils.js`, `puzzles/show.blade.php` |
| 2 | Evaluator diagnostics (`first_error` position or id only) and lecturer-authored scenario consequences | `PuzzleEvaluatorService`, `MissionProgressionService`, puzzle builder forms |
| 3a–3d | Every template executes the answer: ping along the path (Device Mapper), message exchange between hosts (Sequencing), NOC status panel (Scenario), token scan with "Misrouted" flag (Classification), Bezier cables that snap (Matching), scanner beam with signature card or SIGNATURE MISMATCH stamp (Identification) | `public/js/puzzles/Template*.js`, `DeviceMapper.js` |
| 4 | Mission Run: boot console, ticket briefing, tasks in one HUD (map, health, streak, XP), hop transitions, debrief with stars, rank bar, badges and next mission | `student/mission_run.blade.php`, `public/js/run/mission-run.js`, `public/css/netlearn-run.css` |
| 5 | Topology board on the topic page: missions as devices on one path to the quiz, locked devices name the mission that unlocks them, end-to-end banner with the quiz link, vertical on phones | `student/partials/topology_board.blade.php` |
| 6 | Optional story ticket (code, narrator, briefing, debrief) in the mission create/edit forms, with fallbacks | `lecturer/missions/partials/story_fields.blade.php` |
| 7 | Topic quiz instant check: answer locked on the server, packet returns ACK or RST, correct option and explanation shown, HUD, transmission summary, restore on reload; question explanation field for lecturers | `StudentController@checkQuizAnswer`, `student/quiz.blade.php`, `initQuizStepper` |

Stars: 3 = first attempt with no paid hint, 2 = within two attempts or first attempt with a
hint, otherwise 1. Best stars are kept; mission stars are the rounded average.

## New routes

- `POST /puzzles/{id}/hint` (`puzzles.hint`) — reveal a hint, charging the penalty unless free.
- `GET /student/missions/{mission}/run` (`student.mission.run`, `?restart=1`) — 404 when run mode is off.
- `GET /student/missions/{mission}/summary` (`student.mission.summary`) — debrief JSON.
- `POST /student/quiz/{quiz}/check` (`student.quiz.check`, throttled 60/min) — lock one answer and return its verdict.

## Database (additive, nullable or defaulted columns only)

- `2026_10_28_100000_add_progress_star_hint_fields` — `user_puzzle_progress.hints_used`, `best_stars`; `user_mission_progress.best_stars`
- `2026_10_28_100100_add_story_fields_to_missions_table` — `ticket_code`, `briefing_text`, `debrief_text`, `narrator`
- `2026_10_28_100200_add_explanation_to_questions_table` — `questions.explanation`

## Safety switches

- `NETLEARN_RUN_MODE=false` hides Mission Run and restores the original per-puzzle buttons (covered by a test).
- `NETLEARN_DIAGNOSTICS=none` stops the server from returning where the first mistake is.
- Mute is remembered per browser; `prefers-reduced-motion` turns animations into instant state changes; clicking a running animation speeds it up.
- Lecturer preview plays every animation but never writes progress or XP.

## Verification

- `php artisan test`: 130 tests pass on `network_learning_laravel_test`. New suites: `PuzzleDiagnosticsAndHintTest`, `MissionRunTest`, `MissionStoryFieldsTest`, `QuizCheckTest`.
- In the browser (test database): wrong and right answers for Device Mapper, Sequencing, Scenario, Classification, Matching and Identification in lecturer preview; a full Mission Run from boot to debrief; the topology board and its locked tooltip; quiz ACK/RST, locking and restore after reload.

## Not done

- Phase 8 (stretch, CLI troubleshooting puzzle type) was not built.
- The seeded quiz questions have no explanations yet, so students see a generic line after answering until lecturers add them.
- The three migrations above have only been run on the test database.
- The spec's `demo-stable` git tag has not been created.
