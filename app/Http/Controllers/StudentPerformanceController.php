<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Topic;
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
}