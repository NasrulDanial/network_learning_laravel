<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Topic;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\PuzzleAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LecturerDashboardController extends Controller
{
    public function index()
    {
        $totalStudents = User::where('role', 'student')->count();

        $totalTopics = Topic::count();
        $publishedTopics = Topic::where('status', 'published')->count();
        $publishedMissions = Mission::where('status', 'published')->count();

        $totalQuizAttempts = Result::count();
        $totalPuzzleAttempts = PuzzleAttempt::count();
        $totalAttempts = $totalQuizAttempts + $totalPuzzleAttempts;

        $averageScore = (float) (Result::avg('percentage') ?? 0);

        $passedAttempts = Result::where('status', 'Pass')->count();
        $failedAttempts = Result::where('status', 'Fail')->count();
        $passRate = $totalQuizAttempts > 0 ? round(($passedAttempts / $totalQuizAttempts) * 100) : 0;

        $passFailData = [
            'Pass' => $passedAttempts,
            'Fail' => $failedAttempts,
        ];

        // ── Actionable "Content Needing Attention" Audit ──
        $attentionItems = [];

        // 1. Draft Topics
        $draftTopics = Topic::where('status', 'draft')->get();
        foreach ($draftTopics as $dt) {
            $attentionItems[] = [
                'type' => 'Draft Topic',
                'title' => $dt->title,
                'badge' => 'DRAFT',
                'reason' => 'Topic is currently in draft state and hidden from students.',
                'edit_url' => route('lecturer.topics.edit', $dt->topic_id),
                'icon' => 'fa-diagram-project',
                'color' => '#facc15',
            ];
        }

        // 2. Draft Missions
        $draftMissions = Mission::where('status', 'draft')->with('topic')->get();
        foreach ($draftMissions as $dm) {
            $attentionItems[] = [
                'type' => 'Draft Mission',
                'title' => $dm->title . ' (' . ($dm->topic->title ?? 'Unassigned') . ')',
                'badge' => 'DRAFT',
                'reason' => 'Mission is drafted. Review activities and publish to enable student access.',
                'edit_url' => route('lecturer.missions.edit', $dm->mission_id),
                'icon' => 'fa-puzzle-piece',
                'color' => '#facc15',
            ];
        }

        // 3. Published Missions with zero puzzle activities
        $emptyMissions = Mission::where('status', 'published')
            ->has('puzzles', '=', 0)
            ->with('topic')
            ->get();
        foreach ($emptyMissions as $em) {
            $attentionItems[] = [
                'type' => 'Empty Mission',
                'title' => $em->title,
                'badge' => 'NO ACTIVITIES',
                'reason' => 'Mission is published to students but contains zero puzzle activities.',
                'edit_url' => route('lecturer.missions.show', $em->mission_id),
                'icon' => 'fa-triangle-exclamation',
                'color' => '#f87171',
            ];
        }

        // 4. Draft Puzzle Activities in published missions
        $draftPuzzles = Puzzle::where('status', 'draft')->with('mission')->limit(5)->get();
        foreach ($draftPuzzles as $dp) {
            $attentionItems[] = [
                'type' => 'Draft Activity',
                'title' => $dp->question_text . ' (' . ($dp->mission->title ?? 'Mission') . ')',
                'badge' => 'DRAFT ACTIVITY',
                'reason' => 'Activity is saved as draft and cannot be solved by students.',
                'edit_url' => route('lecturer.missions.puzzles.edit', $dp->puzzle_id),
                'icon' => 'fa-pen-ruler',
                'color' => '#38bdf8',
            ];
        }

        $topicPerformance = Result::query()
            ->join('quizzes', 'results.quiz_id', '=', 'quizzes.quiz_id')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->select(
                'topics.title as topic_title',
                DB::raw('COUNT(results.result_id) as total_attempts'),
                DB::raw('AVG(results.percentage) as average_percentage')
            )
            ->groupBy('topics.topic_id', 'topics.title', 'topics.topic_order')
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
                'users.user_id',
                'users.name as student_name',
                'users.email as student_email',
                'quizzes.title as quiz_title',
                'topics.title as topic_title'
            )
            ->orderByDesc('results.date_taken')
            ->limit(6)
            ->get();

        return view('lecturer.dashboard', [
            'pageTitle' => 'Instructor Mission Control',
            'activePage' => 'dashboard',
            'totalStudents' => $totalStudents,
            'totalTopics' => $totalTopics,
            'publishedTopics' => $publishedTopics,
            'publishedMissions' => $publishedMissions,
            'totalAttempts' => $totalAttempts,
            'averageScore' => $averageScore,
            'passRate' => $passRate,
            'passFailData' => $passFailData,
            'topicPerformance' => $topicPerformance,
            'recentResults' => $recentResults,
            'attentionItems' => $attentionItems,
        ]);
    }
}
