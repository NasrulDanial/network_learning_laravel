<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LecturerDashboardController extends Controller
{
    public function index()
    {
        $totalStudents = User::where('role', 'student')->count();

        $totalTopics = Topic::count();

        $totalAttempts = Result::count();

        $averageScore = Result::avg('percentage');
        $averageScore = $averageScore ?? 0;

        $passedAttempts = Result::where('status', 'Pass')->count();

        $failedAttempts = Result::where('status', 'Fail')->count();

        $passFailData = [
            'Pass' => $passedAttempts,
            'Fail' => $failedAttempts,
        ];

        $topicPerformance = Result::query()
            ->join('quizzes', 'results.quiz_id', '=', 'quizzes.quiz_id')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->select(
                'topics.title as topic_title',
                DB::raw('COUNT(results.result_id) as total_attempts'),
                DB::raw('AVG(results.percentage) as average_percentage')
            )
            ->groupBy('topics.topic_id', 'topics.title')
            ->orderBy('topics.topic_order', 'asc')
            ->get();

        $recentResults = Result::query()
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
                'users.name as student_name',
                'users.email as student_email',
                'quizzes.title as quiz_title',
                'topics.title as topic_title'
            )
            ->orderByDesc('results.date_taken')
            ->limit(5)
            ->get();

        return view('lecturer.dashboard', [
            'pageTitle' => 'Lecturer Dashboard',
            'activePage' => 'dashboard',
            'totalStudents' => $totalStudents,
            'totalTopics' => $totalTopics,
            'totalAttempts' => $totalAttempts,
            'averageScore' => $averageScore,
            'passFailData' => $passFailData,
            'topicPerformance' => $topicPerformance,
            'recentResults' => $recentResults,
        ]);
    }
}