<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Puzzle;
use App\Models\UserPuzzleProgress;
use App\Services\PuzzleEvaluatorService;
use App\Services\MissionProgressionService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Auth;

class PuzzleController extends Controller
{
    /**
     * Check whether the authenticated user has clearance to access this puzzle.
     * Returns null if allowed, or an error message string if blocked.
     */
    protected function checkStudentAccess($user, Puzzle $puzzle): ?string
    {
        if (!$user || $user->role !== 'student') {
            return 'Only authenticated students can access puzzle learning activities.';
        }

        if ($puzzle->status !== 'published') {
            return 'This puzzle activity is currently in draft mode.';
        }

        // Students only play activities inside a mission; standalone puzzles are not part of any path
        if (!$puzzle->mission) {
            return 'This activity is not part of a mission.';
        }

        if ($puzzle->mission->status !== 'published') {
            return 'The mission for this activity is not currently published.';
        }

        // Parent topic must be visible and unlocked (same rule as the learning journey)
        $topic = $puzzle->mission->topic;
        if (!$topic || !$topic->isUnlockedFor($user->user_id)) {
            return 'Prerequisite curriculum topic must be completed before accessing this activity.';
        }

        // Parent mission must be unlocked
        if (!$puzzle->mission->isUnlockedFor($user->user_id)) {
            return 'This mission is locked. Complete previous missions to unlock.';
        }

        return null;
    }

    /**
     * Render the puzzle player view for the slide-up sheet modal or standalone view.
     */
    public function play($id)
    {
        $user = Auth::user();
        $puzzle = Puzzle::with(['mission.topic', 'topic'])->findOrFail($id);

        $deniedReason = $this->checkStudentAccess($user, $puzzle);
        if ($deniedReason !== null) {
            abort(403, $deniedReason);
        }

        $progress = UserPuzzleProgress::where('user_id', $user->user_id)
            ->where('puzzle_id', $puzzle->puzzle_id)
            ->first();
        $attemptsUsed = (int) ($progress->attempts_count ?? 0);

        return view('puzzles.show', [
            'puzzle' => $puzzle,
            'inRun' => request()->boolean('run') && config('netlearn.run_mode'),
            'attemptsUsed' => $attemptsUsed,
            // Per-try state drives the status panel (health, star potential, XP at stake)
            'tryAttempts' => (int) ($progress->try_attempts ?? 0),
            'tryHint' => (bool) ($progress->try_hint_used ?? false),
            'penaltyPaid' => (int) ($progress->hints_used ?? 0) > 0,
            'solved' => (bool) ($progress->is_completed ?? false),
            'hasMission' => (bool) $puzzle->mission_id,
            'linkHealth' => $this->poolHealth($user->user_id, $puzzle, $progress),
            // Shown up front only while it still matters for this try (a solved puzzle starts clean)
            'hintRevealed' => (bool) ($progress->try_hint_used ?? false)
                || ((int) ($progress->hints_used ?? 0) > 0 && ! ($progress->is_completed ?? false)),
            'hintFree' => $attemptsUsed >= (int) config('netlearn.free_hint_after') || ($progress->is_completed ?? false),
            'hintPenalty' => (int) config('netlearn.hint_xp_penalty'),
        ]);
    }

    /**
     * Reveal the activity hint. Before the free-hint threshold it costs XP (charged once, never reset);
     * after enough failed attempts, or once solved, it is free.
     */
    public function revealHint($id)
    {
        $user = Auth::user();
        $puzzle = Puzzle::with(['mission.topic', 'topic'])->findOrFail($id);

        $deniedReason = $this->checkStudentAccess($user, $puzzle);
        if ($deniedReason !== null) {
            return response()->json(['status' => 'error', 'message' => $deniedReason], 403);
        }

        if (blank($puzzle->hint)) {
            return response()->json(['status' => 'error', 'message' => 'This activity has no hint.'], 404);
        }

        $progress = UserPuzzleProgress::firstOrCreate(
            ['user_id' => $user->user_id, 'puzzle_id' => $puzzle->puzzle_id],
            ['best_score' => 0, 'attempts_count' => 0, 'is_completed' => false, 'xp_awarded' => 0]
        );

        $free = (int) $progress->attempts_count >= (int) config('netlearn.free_hint_after') || $progress->is_completed;
        $charged = false;
        if (! $free && (int) $progress->hints_used === 0) {
            $progress->hints_used = 1; // permanent: lowers the XP this activity is worth
            $charged = true;
        }
        // Any hint seen during this try rules out 3 stars for it
        $progress->try_hint_used = true;
        $progress->save();

        return response()->json([
            'hint' => $puzzle->hint,
            'charged' => $charged,
            'penalty' => (int) $progress->hints_used > 0 ? (int) config('netlearn.hint_xp_penalty') : 0,
            'try_hint_used' => true,
        ]);
    }

    /** Health shown beside a task: its mission's run pool, or the puzzle's own bar when it has no mission. */
    private function poolHealth(int $userId, Puzzle $puzzle, ?UserPuzzleProgress $progress): int
    {
        $value = $puzzle->mission_id
            ? \App\Models\UserMissionProgress::where('user_id', $userId)->where('mission_id', $puzzle->mission_id)->value('run_health')
            : ($progress->link_health ?? null);

        return $value !== null ? max(1, (int) $value) : 100;
    }

    /**
     * Fetch safe puzzle mission metadata for the Phaser.js canvas.
     * Sensitive solution keys remain strictly server-side.
     */
    public function show($id)
    {
        $user = Auth::user();
        $puzzle = Puzzle::with(['mission.topic', 'topic'])->findOrFail($id);

        $deniedReason = $this->checkStudentAccess($user, $puzzle);
        if ($deniedReason !== null) {
            return response()->json([
                'status'  => 'error',
                'message' => $deniedReason
            ], 403);
        }

        return response()->json($puzzle->getSafeActivityData());
    }

    /**
     * Authoritative server-side evaluation & idempotent progression tracking.
     */
    public function submitAttempt(
        Request $request,
        $id,
        PuzzleEvaluatorService $evaluator,
        MissionProgressionService $progression
    ) {
        $user = Auth::user();
        $puzzle = Puzzle::with(['mission.topic', 'topic'])->findOrFail($id);

        $deniedReason = $this->checkStudentAccess($user, $puzzle);
        if ($deniedReason !== null) {
            return response()->json([
                'status'  => 'error',
                'message' => $deniedReason
            ], 403);
        }

        // Enforce maximum attempts policy if configured
        if ($puzzle->max_attempts > 0) {
            $puzzleProg = \App\Models\UserPuzzleProgress::where('user_id', $user->user_id)
                ->where('puzzle_id', $puzzle->puzzle_id)
                ->first();

            if ($puzzleProg && $puzzleProg->attempts_count >= $puzzle->max_attempts) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Maximum attempts ({$puzzle->max_attempts}) reached for this activity."
                ], 403);
            }
        }

        // Validate type-specific structured answer payload
        $type = $puzzle->puzzle_type;
        $isValidPayload = false;

        switch ($type) {
            case 'identification':
                $isValidPayload = $request->filled('selected_option') || $request->filled('choice_id');
                break;
            case 'matching':
                $isValidPayload = $request->has('pairs') && is_array($request->input('pairs')) && count($request->input('pairs')) > 0;
                break;
            case 'sequencing':
                $isValidPayload = $request->has('ordered_ids') && is_array($request->input('ordered_ids')) && count($request->input('ordered_ids')) > 0;
                break;
            case 'classification':
                $isValidPayload = $request->has('placements') && is_array($request->input('placements')) && count($request->input('placements')) > 0;
                break;
            case 'drag_and_drop':
                $isValidPayload = ($request->has('placements') && is_array($request->input('placements')) && count($request->input('placements')) > 0)
                               || ($request->has('nodes') && is_array($request->input('nodes')));
                break;
            case 'scenario':
                $isValidPayload = $request->filled('choice_id') || $request->filled('selected_option');
                break;
            default:
                $isValidPayload = $request->hasAny(['selected_option', 'pairs', 'ordered_ids', 'placements', 'choice_id', 'nodes']);
                break;
        }

        if (!$isValidPayload) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please complete the activity (select, match or place every item) before submitting.'
            ], 422);
        }

        // Authoritative evaluation: evaluates student answers against database key
        $evaluation = $evaluator->evaluate($puzzle, $request->all());

        // Record attempt, update personal best score, award idempotent XP, evaluate mission unlock
        $result = $progression->recordAttempt($user, $puzzle, $evaluation, $request->all());

        // Check for newly earned badges
        $newBadges = AchievementService::checkAndAward($user->user_id);
        $result['new_badges'] = $newBadges;
        $result['clearance_status'] = $result['clearance_rank'];
        $result['new_rank'] = $result['clearance_rank'];

        return response()->json($result);
    }
}
