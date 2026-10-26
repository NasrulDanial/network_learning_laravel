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
     * Render the puzzle player view for the slide-up sheet modal or standalone view.
     */
    public function play($id)
    {
        $puzzle = Puzzle::with(['mission', 'topic'])->findOrFail($id);

        if ($puzzle->status === 'draft') {
            abort(403, 'This puzzle activity is currently in draft mode.');
        }

        if ($puzzle->mission && $puzzle->mission->status === 'draft') {
            abort(403, 'This mission is not currently published.');
        }

        return view('puzzles.show', compact('puzzle'));
    }

    /**
     * Fetch safe puzzle mission metadata for the Phaser.js canvas.
     * Sensitive solution keys remain strictly server-side.
     */
    public function show($id)
    {
        $puzzle = Puzzle::findOrFail($id);

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
        $puzzle = Puzzle::with(['mission', 'topic'])->findOrFail($id);

        if ($puzzle->status === 'draft') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Activity is currently in draft mode.'
            ], 403);
        }

        // Authoritative evaluation:
        // Evaluates submitted response payload (selected_option, pairs, ordered_ids, placements, choice_id)
        $hasStructuredPayload = $request->hasAny([
            'selected_option', 'pairs', 'ordered_ids', 'placements', 'choice_id', 'nodes'
        ]);

        if ($hasStructuredPayload) {
            $evaluation = $evaluator->evaluate($puzzle, $request->all());
        } else {
            // Fallback for legacy calls
            $isCorrect = $request->boolean('is_correct');
            $score = $isCorrect ? $puzzle->points : 0;
            $evaluation = [
                'is_correct' => $isCorrect,
                'score'      => $score,
                'max_score'  => $puzzle->points,
                'percentage' => $isCorrect ? 100 : 0,
                'feedback'   => $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Simulation verified across all nodes!')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Protocol configuration error detected.')),
                'hint'       => $isCorrect ? null : ($puzzle->hint ?? 'Review PDU flow and collision domain boundaries.'),
            ];
        }

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
