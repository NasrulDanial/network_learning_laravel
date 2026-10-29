<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use App\Models\Progress;
use App\Models\PuzzleAttempt;
use App\Models\UserAchievement;
use App\Models\UserMissionProgress;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\UserPuzzleProgress;
use App\Services\RankProgressionService;
use Illuminate\Http\Request;

class StudentPerformanceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $topicId = $request->input('topic_id');

        $topics = Topic::orderBy('topic_order', 'asc')->get();

        $results = Result::query()
            ->join('users', 'results.user_id', '=', 'users.user_id')
            ->join('quizzes', 'results.quiz_id', '=', 'quizzes.quiz_id')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->select(
                'results.result_id',
                'results.score',
                'results.total_questions',
                'results.percentage',
                'results.status',
                'results.points_earned',
                'results.date_taken',
                'users.user_id',
                'users.name as student_name',
                'users.email as student_email',
                'quizzes.title as quiz_title',
                'topics.topic_id',
                'topics.title as topic_title'
            )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('users.name', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%')
                        ->orWhere('quizzes.title', 'like', '%' . $search . '%')
                        ->orWhere('topics.title', 'like', '%' . $search . '%');
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('results.status', $status);
            })
            ->when($topicId, function ($query) use ($topicId) {
                $query->where('topics.topic_id', $topicId);
            })
            ->orderByDesc('results.date_taken')
            ->paginate(10)
            ->withQueryString();

        $totalAttempts = Result::count();
        $passedAttempts = Result::where('status', 'Pass')->count();
        $failedAttempts = Result::where('status', 'Fail')->count();
        $averageScore = Result::avg('percentage') ?? 0;

        return view('lecturer.student_performance', [
            'pageTitle' => 'Student Performance',
            'activePage' => 'performance',
            'results' => $results,
            'topics' => $topics,
            'search' => $search,
            'status' => $status,
            'topicId' => $topicId,
            'totalAttempts' => $totalAttempts,
            'passedAttempts' => $passedAttempts,
            'failedAttempts' => $failedAttempts,
            'averageScore' => $averageScore,
        ]);
    }

    /**
     * Dedicated Student Performance Detail View.
     */
    public function show(Request $request, User $user)
    {
        if ($user->role !== 'student') {
            abort(404, 'User is not a student operative.');
        }

        $userId = $user->user_id;

        $totalXp = \App\Services\XpService::total($userId);

        $rankInfo = RankProgressionService::getRankTelemetry($totalXp);

        $topicProgress = Topic::query()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->select(
                'topics.topic_id',
                'topics.title',
                'topics.topic_order',
                'progress.learning_status',
                'progress.quiz_status',
                'progress.progress_percentage',
                'progress.total_xp'
            )
            ->orderBy('topics.topic_order', 'asc')
            ->get();

        $quizStatus = in_array($request->input('quiz_status'), ['Pass', 'Fail'], true) ? $request->input('quiz_status') : null;
        $quizAttempts = Result::where('user_id', $userId)
            ->with(['quiz.topic'])
            ->when($quizStatus, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('date_taken')
            ->orderByDesc('result_id')
            ->paginate(8, ['*'], 'quiz_page')
            ->withQueryString();

        // One row per puzzle; its attempts open underneath
        $missionId = $request->integer('mission_id') ?: null;
        $puzzleStatus = in_array($request->input('puzzle_status'), ['solved', 'unsolved'], true) ? $request->input('puzzle_status') : null;
        $puzzleSummary = UserPuzzleProgress::where('user_id', $userId)
            ->where('attempts_count', '>', 0)
            ->with('puzzle.mission.topic')
            ->when($missionId, fn ($q, $m) => $q->whereHas('puzzle', fn ($p) => $p->where('mission_id', $m)))
            ->when($puzzleStatus === 'solved', fn ($q) => $q->where('is_completed', true))
            ->when($puzzleStatus === 'unsolved', fn ($q) => $q->where('is_completed', false))
            ->orderByDesc('last_attempt_at')
            ->orderByDesc('user_puzzle_progress_id')
            ->paginate(10, ['*'], 'puzzle_page')
            ->withQueryString();

        $puzzleTotals = [
            'attempts' => PuzzleAttempt::where('user_id', $userId)->count(),
            'puzzles' => UserPuzzleProgress::where('user_id', $userId)->where('attempts_count', '>', 0)->count(),
        ];

        // Attempts for the puzzles on this page only, latest 10 each
        $attemptsByPuzzle = PuzzleAttempt::where('user_id', $userId)
            ->whereIn('puzzle_id', $puzzleSummary->pluck('puzzle_id'))
            ->orderByDesc('date_taken')
            ->orderByDesc('attempt_id')
            ->get()
            ->groupBy('puzzle_id')
            ->map(fn ($attempts) => $attempts->take(10));

        // Missions this student has worked on, for the filter
        $studentMissions = Mission::whereIn('mission_id', Puzzle::whereIn(
            'puzzle_id',
            UserPuzzleProgress::where('user_id', $userId)->pluck('puzzle_id')
        )->whereNotNull('mission_id')->pluck('mission_id'))
            ->orderBy('topic_id')
            ->orderBy('mission_order')
            ->get(['mission_id', 'title']);

        $earnedAchievements = UserAchievement::where('user_id', $userId)
            ->with('achievement')
            ->orderByDesc('date_earned')
            ->get();

        // Learning Difficulties: attempts with failure or low scores
        $weakAreas = Result::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('status', 'Fail')->orWhere('percentage', '<', 70);
            })
            ->with(['quiz.topic'])
            ->orderBy('percentage', 'asc')
            ->limit(5)
            ->get();

        return view('lecturer.student_detail', [
            'pageTitle' => 'Operative Dossier: ' . $user->name,
            'activePage' => 'performance',
            'student' => $user,
            'rankInfo' => $rankInfo,
            'totalXp' => $totalXp,
            'topicProgress' => $topicProgress,
            'quizAttempts' => $quizAttempts,
            'quizStatus' => $quizStatus,
            'puzzleSummary' => $puzzleSummary,
            'puzzleTotals' => $puzzleTotals,
            'attemptsByPuzzle' => $attemptsByPuzzle,
            'studentMissions' => $studentMissions,
            'missionId' => $missionId,
            'puzzleStatus' => $puzzleStatus,
            'earnedAchievements' => $earnedAchievements,
            'weakAreas' => $weakAreas,
        ]);
    }
}
