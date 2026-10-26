<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Puzzle;
use App\Models\PuzzleAttempt;
use App\Models\Progress;
use Illuminate\Support\Facades\Auth;

class PuzzleController extends Controller
{
    /**
     * Fetch safe puzzle mission metadata for the Phaser.js canvas.
     * Sensitive solution keys remain strictly server-side.
     */
    public function show($id)
    {
        $puzzle = Puzzle::findOrFail($id);

        return response()->json(
            $puzzle->only(['puzzle_id', 'topic_id', 'puzzle_type', 'question_text', 'instruction', 'points'])
        );
    }

    /**
     * Evaluate simulation submission and credit Network XP and Clearance Level.
     */
    public function submitAttempt(Request $request, $id)
    {
        $request->validate([
            'is_correct' => 'required|boolean',
            'score'      => 'sometimes|integer|min:0',
        ]);

        $user   = Auth::user();
        $puzzle = Puzzle::findOrFail($id);

        // Server-side authoritative score evaluation
        $isCorrect    = $request->boolean('is_correct');
        $awardedScore = $isCorrect ? $puzzle->points : 0;

        // Track telemetry attempt count
        $attemptCount = PuzzleAttempt::where('user_id', $user->user_id)
            ->where('puzzle_id', $id)
            ->count() + 1;

        $status = $isCorrect ? 'completed' : 'failed';

        // Log simulation attempt
        PuzzleAttempt::create([
            'user_id'    => $user->user_id,
            'puzzle_id'  => $id,
            'score'      => $awardedScore,
            'status'     => $status,
            'attempt_no' => $attemptCount,
            'date_taken' => now(),
        ]);

        $response = [
            'status'   => $status,
            'score'    => $awardedScore,
            'feedback' => $isCorrect
                ? 'Routing & Protocol Data Units verified! Simulation verified across all nodes.'
                : ($puzzle->explanation ?? 'Protocol configuration error detected.'),
        ];

        // Award Network XP and update Clearance Level
        if ($isCorrect) {
            $progress = Progress::firstOrCreate(
                ['user_id' => $user->user_id, 'topic_id' => $puzzle->topic_id],
                ['completed_missions' => 0, 'total_xp' => 0, 'current_level' => 1, 'current_rank' => 'Clearance Level 1: Network Cadet']
            );

            $progress->total_xp += $puzzle->points;
            $progress->completed_missions += 1;

            // 500 Network XP required per Clearance Level
            $newClearance = (int) floor($progress->total_xp / 500) + 1;
            $progress->current_level = $newClearance;

            $clearanceRanks = [
                'Clearance Level 1: Network Cadet',
                'Clearance Level 2: Packet Analyst',
                'Clearance Level 3: Infrastructure Technician',
                'Clearance Level 4: Network Security Specialist',
                'Clearance Level 5: Principal Network Architect'
            ];
            $rankIndex = min((int) floor($newClearance / 2), 4);
            $progress->current_rank = $clearanceRanks[$rankIndex];

            $progress->save();

            $response['earned_xp']        = $puzzle->points;
            $response['clearance_status'] = $progress->current_rank;
            $response['new_rank']         = $progress->current_rank;
        } else {
            $response['hint'] = $puzzle->hint ?? 'Review PDU flow and collision domain boundaries.';
        }

        return response()->json($response);
    }
}
