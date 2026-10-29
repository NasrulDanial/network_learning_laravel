<?php

/*
|--------------------------------------------------------------------------
| NetLearn game-feel settings
|--------------------------------------------------------------------------
| run_mode           Mission Run (continuous full-screen play). false = old
|                    single-puzzle flow, an instant fallback for demos.
| sound_default_on   Sound effects start enabled (students can mute; remembered).
| hint_xp_penalty    XP removed from an activity when its hint is bought early.
| free_hint_after    Failed attempts after which the hint becomes free.
| ttl_seconds        Per-puzzle TTL shown in the run HUD (0 = off). Never fails a puzzle.
| diagnostics_level  'first_error' lets animations stop where an answer first fails
|                    (never reveals the correct answer); 'none' disables it.
| quiz_pass_xp       XP a quiz is worth in total (capped per quiz across attempts).
| quiz_first_attempt_xp  Consolation XP for a failed first attempt (counts toward the cap).
| quiz_lives         Wrong answers allowed per quiz attempt; the last one ends it (LINK DOWN).
*/

return [
    'run_mode' => (bool) env('NETLEARN_RUN_MODE', true),
    'sound_default_on' => (bool) env('NETLEARN_SOUND', true),
    'hint_xp_penalty' => (int) env('NETLEARN_HINT_PENALTY', 10),
    'free_hint_after' => (int) env('NETLEARN_FREE_HINT_AFTER', 2),
    'ttl_seconds' => (int) env('NETLEARN_TTL', 0),
    'diagnostics_level' => env('NETLEARN_DIAGNOSTICS', 'first_error'),
    'quiz_pass_xp' => (int) env('NETLEARN_QUIZ_PASS_XP', 100),
    'quiz_first_attempt_xp' => (int) env('NETLEARN_QUIZ_FIRST_XP', 20),
    'quiz_lives' => (int) env('NETLEARN_QUIZ_LIVES', 3),
];
