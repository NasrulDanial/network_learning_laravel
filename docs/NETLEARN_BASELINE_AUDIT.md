# NetLearn Baseline Audit

**Date:** 2026-10-26  
**Environment:** Laravel 10.3 / PHP 8.1.16 / MySQL / Windows (XAMPP)  
**Git Branch:** `feature/puzzle-based-learning`

---

## 1. Migration Status (`php artisan migrate:status`)

| Migration File | Batch | Status |
|---|---|---|
| `2014_10_12_000000_create_users_table` | 1 | Ran |
| `2014_10_12_100000_create_password_reset_tokens_table` | 1 | Ran |
| `2019_08_19_000000_create_failed_jobs_table` | 1 | Ran |
| `2019_12_14_000001_create_personal_access_tokens_table` | 1 | Ran |
| `2026_07_06_141504_create_topics_table` | 1 | Ran |
| `2026_07_06_141511_create_quizzes_table` | 1 | Ran |
| `2026_07_06_141518_create_questions_table` | 1 | Ran |
| `2026_07_06_141525_create_results_table` | 1 | Ran |
| `2026_07_06_141532_create_progress_table` | 1 | Ran |
| `2026_10_25_075532_create_puzzles_table` | 2 | Ran |
| `2026_10_25_075533_create_achievements_table` | 2 | Ran |
| `2026_10_25_075533_create_puzzle_attempts_table` | 2 | Ran |
| `2026_10_25_075534_add_gamification_to_progress_table` | 2 | Ran |
| `2026_10_25_075534_create_user_achievements_table` | 2 | Ran |

---

## 2. Confirmed Architectural Deficiencies & Discrepancies

1. **Missing `Mission` entity**: `puzzles` table references `topic_id` directly; there is no `missions` table to support the educational hierarchy **Topic → Missions → Puzzle Activities**.
2. **Hard-coded Phaser Scenes**: `DeviceMapper.js` and `OsiSequencer.js` contain hardcoded content rather than dynamic database content.
3. **Client-Trusted Scoring Vulnerability**: `PuzzleController::submitAttempt()` previously accepted `is_correct` and `score` from browser POST data, allowing dev-tools tampering.
4. **Duplicate XP / Farming**: Submissions on repeat attempts re-awarded XP repeatedly rather than only awarding incremental personal-best increases.
5. **Model / Schema Mismatches**:
   - `UserAchievement` model defined `$primaryKey = 'id'`, while migration uses `user_achievement_id`.
   - `Achievement` model `$fillable` omitted `xp_reward` and `badge_icon`.
   - `Progress` model `$fillable` omitted `total_xp`, `completed_missions`, `current_level`, and `current_rank`.
6. **Public Role Escalation**: `AuthController::register()` validated a submitted `role` field allowing unauthorized `lecturer` registrations. Public self-registration must strictly create `student` accounts.
7. **Conflation of Quiz Questions and Puzzle Steps**: Temporary step JSON in QuestionController overloaded traditional MCQs with simulation steps.

---

## 3. Preservation Commitments

- Zero destructive operations (`migrate:fresh`, `db:wipe`, `truncate` are strictly prohibited).
- Preserve existing topics, quizzes, questions, results, progress, and user accounts.
- Backward-compatible schema evolution through forward-only corrective migrations.
- Complete documentation of all audited assets and clean-up in `docs/NETLEARN_CLEANUP_LOG.md`.
