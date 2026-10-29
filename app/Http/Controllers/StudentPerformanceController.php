<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use App\Models\Progress;
use App\Models\PuzzleAttempt;
use App\Models\UserAchievement;
use App\Models\UserMissionProgress;
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
    public function show(User $user)
    {
        if ($user->role !== 'student') {
            abort(404, 'User is not a student operative.');
        }

        $userId = $user->user_id;

        $quizPoints = (int) Result::where('user_id', $userId)->sum('points_earned');
        $pblPoints = (int) Progress::where('user_id', $userId)->sum('total_xp');
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

        $quizAttempts = Result::where('user_id', $userId)
            ->with(['quiz.topic'])
            ->orderByDesc('date_taken')
            ->get();

        $puzzleAttempts = PuzzleAttempt::where('user_id', $userId)
            ->with(['puzzle.mission', 'puzzle.topic'])
            ->orderByDesc('date_taken')
            ->limit(20)
            ->get();

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
            'puzzleAttempts' => $puzzleAttempts,
            'earnedAchievements' => $earnedAchievements,
            'weakAreas' => $weakAreas,
        ]);
    }
}
