# NetLearn post-testing fixes — 29 Oct 2026

Implementation notes for `NetLearn_Testing_Fixes_Plan.txt`, branch `fix/post-testing` (from `feature/wow-upgrade`).

## Decisions applied

- **D1** Every quiz attempt has 3 lives (`NETLEARN_QUIZ_LIVES`). The 3rd wrong answer ends it (LINK DOWN) as a Fail with 0 XP.
- **D2** Leaving after at least one checked answer records a Fail with the score so far and 0 XP.
- **D3** Puzzle XP is earned only by solving. Wrong answers earn nothing; their partial score is still logged.
- **D4** Study notes are display-only: "read" is tracked in the browser and never gates anything. Missions do gate the quiz.

## Formulas

| What | Rule |
|---|---|
| Puzzle XP (once) | solved ? points − (hint ever bought ? 10 : 0) : 0 |
| Puzzle stars (per try) | 3 = first try with no hint seen in this try; 2 = within 2 tries; else 1. Best kept; the try resets on a solve or a Mission Run restart |
| Mission cleared | every required puzzle solved (all published puzzles if none are flagged) |
| Topic progress | 100 when completed; otherwise 10 + round(70 × missions cleared / missions) |
| Topic completed | all missions cleared AND (quiz passed OR no quiz). Never downgraded |
| Quiz unlocked | topic unlocked AND all missions cleared |
| Quiz XP per quiz | first-attempt fail 20, a pass earns what is left of 100, LINK DOWN or abandoned 0 |
| Total XP | `XpService::total()` = Σ progress.total_xp + Σ results.points_earned |

## What changed, by step

1. **Calculations.** `XpService`, `TopicProgressService`, and a rewritten `MissionProgressionService::recordAttempt` / `evaluateMissionCompletion`. Stars are counted per try (`try_attempts`, `try_hint_used`). `pass_mark` is hidden from the lecturer forms and lists (the column is kept). The quiz header shows the XP still claimable.
2. **Repair command.** `php artisan netlearn:recalculate {--user=} {--dry-run}` reapplies the rules to existing data and prints a before/after table. `--dry-run` runs the same code in a transaction and rolls it back.
3. **Quiz lock.** `Topic::missionsClearedBy()` and `assessmentUnlockedFor()`. The quiz page redirects to the Missions tab; check and submit return 403. The quiz hub shows mission progress.
4. **Quiz flow.** Lives counted on the server; question and option order shuffled per attempt (stable on reload); Continue / Finish only; LINK DOWN dialog; leave confirmation with `POST /student/quiz/{quiz}/abandon`. `results.ended_reason` records how an attempt ended.
5. **Topic tabs.** Overview, Study Notes (split on ALL-CAPS headings, with contents, a jump menu on phones, and Expand/Collapse all), Missions ("x of y tasks solved"), Assessment. Deep links via `?tab=` and `#`. Without JavaScript every panel is shown.
6. **Lecturer preview.** Centred dialog on desktop and bottom sheet on phones. The embedded page is compact and its canvas fits the frame. Scroll is locked while open and focus returns to the button.
7. **Quiz search.** Glass filter panel with an icon; `input[type=search]` gets the global input styles.
8. **Dossier.** One row per puzzle with expandable attempts and a Struggling flag; both logs paginate and filter; readable dates; Link down and Abandoned pills.

## Database

- `2026_10_29_100000_add_try_fields_to_user_puzzle_progress` — `try_attempts`, `try_hint_used`
- `2026_10_29_100100_add_ended_reason_to_results` — `ended_reason`

Both have been run on the dev database (see the note below) and the test database.

## Verification

- `php artisan test`: 160 tests pass on `network_learning_laravel_test`. New suites: `XpRulesTest`, `StarsPerTryTest`, `MissionCompletionTest`, `TopicProgressTest`, `QuizXpCapTest`, `RecalculateCommandTest`, `QuizLockTest`, `QuizLivesTest`, `QuizAbandonTest`, `TopicTabsTest`, `DossierTest`.
- Browser (test database):
  - topic tabs on desktop and phone;
  - quiz: Continue, three wrong answers ending in LINK DOWN and a Fail result, LINK DOWN restored on reload, and leaving through the sidebar recording an abandoned Fail;
  - the centred preview at 1366×768 with the whole canvas visible, Esc to close, focus returned to the button;
  - the quiz search box;
  - the grouped, paginated dossier.

## Notes and open items

- **Dev database.** On 29 Oct the two migrations above were run on the dev database by mistake (`php artisan migrate --env=testing` falls back to `.env` because there is no `.env.testing`). They only add columns with defaults, and no rows were changed. A backup taken right after is in `storage/app/backups/network_learning_laravel_20261029_155203_after_try_columns.sql`.
- **`netlearn:recalculate` was run on the dev database on 29 Oct** after a backup (`storage/app/backups/network_learning_laravel_20261029_162208_pre_recalculate.sql`):
  - student1: 3990 → 300 XP. Topic 2 held 3200 XP with no solved puzzles or cleared missions behind it, and quiz XP of 240 was capped at 100.
  - tester1: 1420 → 1380 XP (quiz cap).
  - Topics already completed stayed completed. A second dry run reports no further changes. To undo, restore that backup.
- The plan's `post-testing-fixes` git tag has not been created.
