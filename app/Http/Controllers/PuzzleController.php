<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Puzzle;
use App\Models\PuzzleAttempt;
use App\Models\Progress;
use App\Models\Mission;
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

        // Check if parent topic is unlocked
        $topic = $puzzle->topic ?? ($puzzle->mission ? $puzzle->mission->topic : null);
        if ($topic && (int)$topic->topic_order > 1) {
            $prevTopic = \App\Models\Topic::where('topic_order', '<', $topic->topic_order)
                ->orderByDesc('topic_order')
                ->first();

            if ($prevTopic) {
                $prevProgress = Progress::where('user_id', $user->user_id)
                    ->where('topic_id', $prevTopic->topic_id)
                    ->first();

                if (!$prevProgress || $prevProgress->learning_status !== 'Completed') {
                    return 'Prerequisite curriculum topic must be completed before accessing this activity.';
                }
            }
        }

        // Check if parent mission is unlocked
        if ($puzzle->mission) {
            $mission = $puzzle->mission;
            $firstMission = Mission::where('topic_id', $mission->topic_id)
                ->where('status', 'published')
                ->orderBy('mission_order', 'asc')
                ->first();

            if ($firstMission && (int)$firstMission->mission_id !== (int)$mission->mission_id) {
                $missionProg = \App\Models\UserMissionProgress::where('user_id', $user->user_id)
                    ->where('mission_id', $mission->mission_id)
                    ->first();

                if (!$missionProg || !$missionProg->is_unlocked) {
                    return 'This mission is locked. Complete previous missions to unlock.';
                }
            }
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
                'message' => "Invalid simulation telemetry payload for '{$type}'. Missing required answer parameters."
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

    /**
     * Store or update puzzle simulation configuration authored by a lecturer.
     */
    public function configurePuzzle(Request $request)
    {
        $validated = $request->validate([
            'topic_id'       => 'nullable|integer|exists:topics,topic_id',
            'quiz_id'        => 'nullable|integer|exists:quizzes,quiz_id',
            'mission_id'     => 'nullable|integer|exists:missions,mission_id',
            'puzzle_type'    => 'required|string',
            'points'         => 'sometimes|integer|min:25|max:1000',
            'instruction'    => 'nullable|string',
            'question_text'  => 'nullable|string',
            'hint'           => 'nullable|string',
            'explanation'    => 'nullable|string',
            'correct_answer' => 'nullable',
            'activity_data'  => 'nullable',
        ]);

        $topicId = $validated['topic_id'] ?? null;
        if (!$topicId && !empty($validated['quiz_id'])) {
            $quiz = \App\Models\Quiz::find($validated['quiz_id']);
            $topicId = $quiz ? $quiz->topic_id : null;
        }

        if (!$topicId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Valid topic_id or quiz_id is required to attach simulation.'
            ], 422);
        }

        $missionId = $validated['mission_id'] ?? null;
        if (!$missionId) {
            $mission = Mission::where('topic_id', $topicId)->first();
            $missionId = $mission ? $mission->mission_id : null;
        }

        $typeMap = [
            'topology' => 'drag_and_drop',
            'routing'  => 'scenario',
            'protocol' => 'sequencing',
            'subnet'   => 'identification',
        ];
        $dbType = $typeMap[$validated['puzzle_type']] ?? $validated['puzzle_type'];

        $answer = $request->input('correct_answer');
        if (is_string($answer)) {
            $decoded = json_decode($answer, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $answer = $decoded;
            }
        }
        if (empty($answer)) {
            $answer = ['configured' => true, 'timestamp' => now()->toIso8601String()];
        }

        $activityData = $request->input('activity_data');
        if (is_string($activityData)) {
            $decoded = json_decode($activityData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $activityData = $decoded;
            }
        }

        $attributes = [];
        if ($request->filled('puzzle_id')) {
            $attributes['puzzle_id'] = (int) $request->input('puzzle_id');
        } else {
            $attributes = [
                'topic_id'    => $topicId,
                'puzzle_type' => $dbType,
            ];
        }

        $puzzle = Puzzle::updateOrCreate(
            $attributes,
            [
                'mission_id'     => $missionId,
                'puzzle_type'    => $dbType,
                'question_text'  => $validated['question_text'] ?? ('Interactive simulation challenge for topic #' . $topicId),
                'instruction'    => $validated['instruction'] ?? 'Complete the network simulation according to laboratory specifications.',
                'correct_answer' => $answer,
                'activity_data'  => $activityData,
                'points'         => $validated['points'] ?? 150,
                'hint'           => $validated['hint'] ?? null,
                'explanation'    => $validated['explanation'] ?? null,
                'status'         => 'published',
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Simulation board configuration successfully saved and synchronized!',
            'puzzle'  => [
                'puzzle_id'   => $puzzle->puzzle_id,
                'topic_id'    => $puzzle->topic_id,
                'mission_id'  => $puzzle->mission_id,
                'puzzle_type' => $puzzle->puzzle_type,
                'points'      => $puzzle->points,
            ]
        ]);
    }

    /**
     * Fetch existing authored puzzle board state for lecturer editing.
     */
    public function getBoardState(Request $request, $topicId)
    {
        $puzzle = Puzzle::where('topic_id', $topicId)->first();
        if (!$puzzle) {
            return response()->json(['status' => 'empty', 'puzzle' => null]);
        }

        return response()->json([
            'status' => 'success',
            'puzzle' => [
                'puzzle_id'      => $puzzle->puzzle_id,
                'topic_id'       => $puzzle->topic_id,
                'mission_id'     => $puzzle->mission_id,
                'puzzle_type'    => $puzzle->puzzle_type,
                'points'         => $puzzle->points,
                'instruction'    => $puzzle->instruction,
                'correct_answer' => $puzzle->correct_answer,
                'activity_data'  => $puzzle->activity_data,
            ]
        ]);
    }
}
