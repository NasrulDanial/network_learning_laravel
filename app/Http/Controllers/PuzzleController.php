<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Puzzle;
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

        if ($puzzle->mission && $puzzle->mission->status !== 'published') {
            return 'The mission for this activity is not currently published.';
        }

        // Parent topic must be visible and unlocked (same rule as the learning journey)
        $topic = $puzzle->mission ? $puzzle->mission->topic : $puzzle->topic;
        if ($topic && !$topic->isUnlockedFor($user->user_id)) {
            return 'Prerequisite curriculum topic must be completed before accessing this activity.';
        }

        // Parent mission must be unlocked
        if ($puzzle->mission && !$puzzle->mission->isUnlockedFor($user->user_id)) {
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

        return view('puzzles.show', compact('puzzle'));
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
