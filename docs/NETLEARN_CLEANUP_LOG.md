# NetLearn Cleanup Log

This log records every candidate file audited during the NetLearn codebase refinement, the reference check evidence, and the safe action taken.

| File Audited | Reference Search Evidence | Action & Justification |
|---|---|---|
| `public/js/puzzles/OsiSequencer.js` | Audited. Zero references found in views, scripts, controllers, or tests. Completely superseded by modular data-driven `TemplateSequencing.js`. | **Safely Removed**. Kept codebase clean and free of legacy hard-coded scripts. |
| `public/js/puzzle-builder.js` | Audited. Legacy prototype script before modal consolidation. | Preserved as harmless standalone asset; not referenced by current production Blade views. |
| `resources/views/welcome.blade.php` | Root route `/` renders `landing`. No route renders `welcome`. | Retained as standard Laravel scaffold fallback to avoid unexpected view:cache errors. |
| `resources/css/app.css` & `resources/js/app.js` | Standard Laravel Vite build entry points. | Retained & active. Preserves valid `npm run build` capability without breaking pipeline. |
| `resources/views/vendor/pagination/*` | Laravel default published pagination views. | Retained to ensure stable paginator rendering across all tables. |
| `public/js/netlearn-app.js` | Imported by `resources/views/layouts/app.blade.php`. | Retained & active. Provides UI micro-interactions. |
| `public/js/theme.js` | Imported in header for dark/light theme persistence. | Retained & active. |
| `public/css/style.css` & `netlearn-premium.css` | Linked in layout header. | Retained & active. Coherent bespoke design system. |
| `resources/views/lecturer/quizzes/index.blade.php` (legacy "Steps" slide-over, "Design Board" canvas, gamification settings modal) | Its JS called only the routes below; no other view or test used them. | **Replaced** with a plain quiz library (Edit Questions / Settings / Delete). The board saved unsolvable puzzles into Mission 1 and "Steps" inserted placeholder questions into real quizzes. |
| Routes `lecturer.quizzes.steps*`, `lecturer.puzzles.configure`, `lecturer.puzzles.state`; `QuestionController::getStepsJson/storeStepJson/destroyStepJson`; `PuzzleController::configurePuzzle/getBoardState` | `grep` over `app/`, `resources/`, `routes/`, `tests/`, `public/js/`: only the replaced view and three tests referenced them. | **Removed**. Tests replaced by `QuizManagementTest::test_legacy_steps_and_board_endpoints_are_removed`. |
| `Quiz::puzzle()` and `Topic::puzzle()` (hasOne by topic) | No call sites after the board removal. | **Removed**. Topics/missions own many puzzles via `missions` and `puzzles` relations. |
| Inline sidebar script in `resources/views/layouts/footer.blade.php` | Duplicated the toggle in `public/js/netlearn-app.js`; both fired on every click, so the mobile menu opened and immediately closed. | **Removed**. One shared implementation in `netlearn-app.js`. |
| `public/css/netlearn-polish.css` | New; linked from `layouts/app.blade.php` after the two existing stylesheets. | **Added**. Shared helpers, light-theme corrections for inline colours, responsive rules. |
| `database/seeders/NetLearnSeeder.php`, `database/seeders/NetworkingMissionSeeder.php` | Only `DatabaseSeeder` called them. They conflicted (one matched topics by title, the other overwrote topics by order) and put a different topic at stage 2 than the curriculum. | **Removed**. Replaced by `DemoUserSeeder` (same demo accounts), `CurriculumSeeder` (5-topic curriculum from `database/seeders/data/netlearn_curriculum.php`) and `AchievementSeeder` (same four badges, corrected descriptions, plus six milestone badges). |
