<?php

namespace App\Http\Controllers;

use App\Models\Progress;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Topic;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\UserMissionProgress;
use App\Models\UserPuzzleProgress;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Services\RankProgressionService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->user()->user_id;

        $quizPoints = (int) Result::where('user_id', $userId)->sum('points_earned');
        $pblPoints = (int) Progress::where('user_id', $userId)->sum('total_xp');
        $totalPoints = $quizPoints + $pblPoints;

        $rankInfo = RankProgressionService::getRankTelemetry($totalPoints);

        $completedTopics = Progress::where('user_id', $userId)
            ->where('learning_status', 'Completed')
            ->count();

        $completedMissions = UserMissionProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $averageQuizScore = (float) (Result::where('user_id', $userId)->avg('percentage') ?? 0);
        $badgesEarnedCount = UserAchievement::where('user_id', $userId)->count();

        $overallProgress = (float) (Progress::where('user_id', $userId)->avg('progress_percentage') ?? 0);

        // Find Next Up "Continue Learning" target: the first visible topic not yet completed.
        // Every earlier visible topic is completed, so this topic is always unlocked.
        $nextTopic = Topic::query()
            ->visibleToStudents()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->where(function ($q) {
                $q->whereNull('progress.learning_status')
                    ->orWhere('progress.learning_status', '!=', 'Completed');
            })
            ->orderBy('topics.topic_order', 'asc')
            ->select('topics.*')
            ->first();

        $continueTarget = null;
        $recentPuzzles = collect();

        if ($nextTopic) {
            $completedMissionIds = UserMissionProgress::where('user_id', $userId)
                ->where('is_completed', true)
                ->pluck('mission_id');

            $nextMission = Mission::where('topic_id', $nextTopic->topic_id)
                ->where('status', 'published')
                ->whereNotIn('mission_id', $completedMissionIds)
                ->orderBy('mission_order', 'asc')
                ->first();

            $continueTarget = [
                'topic_id' => $nextTopic->topic_id,
                'topic_title' => $nextTopic->title,
                'topic_order' => $nextTopic->topic_order,
                'mission_title' => $nextMission ? $nextMission->title : 'Core Assessment Lab',
                'url' => route('student.topic', $nextTopic->topic_id),
            ];

            // Suggest only puzzles the student can actually open and has not finished yet
            if ($nextMission && $nextMission->isUnlockedFor($userId)) {
                $completedPuzzleIds = UserPuzzleProgress::where('user_id', $userId)
                    ->where('is_completed', true)
                    ->pluck('puzzle_id');

                $recentPuzzles = Puzzle::where('mission_id', $nextMission->mission_id)
                    ->where('status', 'published')
                    ->whereNotIn('puzzle_id', $completedPuzzleIds)
                    ->with(['mission', 'topic'])
                    ->orderBy('activity_order', 'asc')
                    ->orderBy('puzzle_id', 'asc')
                    ->take(2)
                    ->get();
            }
        }

        $latestAchievement = UserAchievement::with('achievement')
            ->where('user_id', $userId)
            ->orderByDesc('date_earned')
            ->first();

        return view('student.dashboard', [
            'pageTitle' => 'Student Dashboard',
            'activePage' => 'dashboard',
            'totalPoints' => $totalPoints,
            'overallProgress' => $overallProgress,
            'completedTopics' => $completedTopics,
            'completedMissions' => $completedMissions,
            'averageQuizScore' => $averageQuizScore,
            'badgesEarnedCount' => $badgesEarnedCount,
            'recentPuzzles' => $recentPuzzles,
            'rankInfo' => $rankInfo,
            'continueTarget' => $continueTarget,
            'latestAchievement' => $latestAchievement,
        ]);
    }

    public function learning()
    {
        $userId = auth()->user()->user_id;

        $topics = Topic::query()
            ->visibleToStudents()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->withCount(['missions' => function ($q) {
                $q->where('status', 'published');
            }])
            ->select(
                'topics.topic_id',
                'topics.title',
                'topics.description',
                'topics.cover_image',
                'topics.objectives',
                'topics.difficulty',
                'topics.estimated_time_minutes',
                'topics.status',
                'topics.topic_order',
                'progress.learning_status',
                'progress.quiz_status',
                'progress.progress_percentage',
                'progress.completed_missions',
                'progress.total_xp'
            )
            ->orderBy('topics.topic_order', 'asc')
            ->get();

        return view('student.learning', [
            'pageTitle' => 'Networking Journey',
            'activePage' => 'learning',
            'topics' => $topics,
        ]);
    }

    public function topic(Topic $topic)
{
    $userId = auth()->user()->user_id;

    if (! $topic->isUnlockedFor($userId)) {
        return redirect()->route('student.learning');
    }

    /*
     * Record that the student has opened the topic.
     * Do not reduce progress if the topic is already completed.
     */
    $existingProgress = Progress::where('user_id', $userId)
        ->where('topic_id', $topic->topic_id)
        ->first();

    if ($existingProgress) {
        if ($existingProgress->learning_status !== 'Completed') {
            $existingProgress->learning_status = 'In Progress';

            if ((int) $existingProgress->progress_percentage < 25) {
                $existingProgress->progress_percentage = 25;
            }

            $existingProgress->save();
        }
    } else {
        Progress::create([
            'user_id' => $userId,
            'topic_id' => $topic->topic_id,
            'learning_status' => 'In Progress',
            'quiz_status' => 'Not Attempted',
            'progress_percentage' => 25,
        ]);
    }

    $quiz = Quiz::where('topic_id', $topic->topic_id)
        ->orderBy('quiz_id', 'asc')
        ->first();

    // Load PBL missions with their published puzzles and student progress
    $missions = Mission::where('topic_id', $topic->topic_id)
        ->where('status', 'published')
        ->withCount(['puzzles' => function ($q) {
            $q->where('status', 'published');
        }])
        ->orderBy('mission_order', 'asc')
        ->get();

    // Attach student's mission progress and puzzle progress to each mission
    $missionProgressMap = UserMissionProgress::where('user_id', $userId)
        ->whereIn('mission_id', $missions->pluck('mission_id'))
        ->get()
        ->keyBy('mission_id');

    // First mission is always unlocked; later ones once the previous mission is completed
    $previousCompleted = true;
    foreach ($missions as $m) {
        $prog = $missionProgressMap->get($m->mission_id);
        $m->student_is_unlocked = $previousCompleted || ($prog && $prog->is_unlocked);
        $previousCompleted = $prog && $prog->is_completed;
        $m->student_is_completed = $prog && $prog->is_completed;
        $m->student_score_pct = $prog ? $prog->best_score_percentage : 0;

        // Load puzzles for this mission with student's puzzle progress
        $puzzles = $m->puzzles()
            ->where('status', 'published')
            ->orderBy('activity_order', 'asc')
            ->get();

        $puzzleProgressMap = UserPuzzleProgress::where('user_id', $userId)
            ->whereIn('puzzle_id', $puzzles->pluck('puzzle_id'))
            ->get()
            ->keyBy('puzzle_id');

        foreach ($puzzles as $puzzle) {
            $pProg = $puzzleProgressMap->get($puzzle->puzzle_id);
            $puzzle->student_is_completed = $pProg && $pProg->is_completed;
            $puzzle->student_best_score = $pProg ? $pProg->best_score : 0;
            $puzzle->student_attempts = $pProg ? $pProg->attempts_count : 0;
        }

        $m->setRelation('puzzles', $puzzles);
    }

    return view('student.topic', [
        'pageTitle' => 'Learning Topic',
        'activePage' => 'learning',
        'topic'     => $topic,
        'quiz'      => $quiz,
        'missions'  => $missions,
    ]);
}

public function quizHub()
{
    $userId = auth()->user()->user_id;

    $quizzes = Quiz::query()
        ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
        ->where(function ($q) {
            $q->where('topics.status', 'published')
                ->orWhereNull('topics.status');
        })
        ->leftJoin('progress', function ($join) use ($userId) {
            $join->on('topics.topic_id', '=', 'progress.topic_id')
                ->where('progress.user_id', '=', $userId);
        })
        ->leftJoin('questions', 'quizzes.quiz_id', '=', 'questions.quiz_id')
        ->select(
            'quizzes.quiz_id',
            'quizzes.topic_id',
            'quizzes.title as quiz_title',
            'quizzes.passing_mark',
            'topics.title as topic_title',
            'topics.description as topic_description',
            'topics.topic_order',
            'progress.learning_status',
            'progress.quiz_status',
            'progress.progress_percentage'
        )
        ->selectRaw('COUNT(questions.question_id) as total_questions')
        ->groupBy(
            'quizzes.quiz_id',
            'quizzes.topic_id',
            'quizzes.title',
            'quizzes.passing_mark',
            'topics.title',
            'topics.description',
            'topics.topic_order',
            'progress.learning_status',
            'progress.quiz_status',
            'progress.progress_percentage'
        )
        ->orderBy('topics.topic_order', 'asc')
        ->orderBy('quizzes.quiz_id', 'asc')
        ->get();

    // Lock state follows the topic sequence, not the quiz list (topics may have 0 or several quizzes)
    $unlockedTopicIds = $this->unlockedTopicIds($userId);
    foreach ($quizzes as $quizItem) {
        $quizItem->is_unlocked = in_array((int) $quizItem->topic_id, $unlockedTopicIds, true);
    }

    return view('student.quiz_hub', [
        'pageTitle' => 'Quiz Challenges',
        'activePage' => 'quiz',
        'quizzes' => $quizzes,
    ]);
}

public function quiz(Quiz $quiz)
{
    $userId = auth()->user()->user_id;

    $quiz->load('topic', 'questions');

    if (! $quiz->topic) {
        return redirect()->route('student.quiz');
    }

    if (! $quiz->topic->isUnlockedFor($userId)) {
        return redirect()->route('student.quiz');
    }

    if ($quiz->questions->count() < 1) {
        return redirect()
            ->route('student.quiz')
            ->withErrors([
                'quiz' => 'This quiz does not have questions yet.',
            ]);
    }

    return view('student.quiz', [
        'pageTitle' => 'Quiz Challenge',
        'activePage' => 'quiz',
        'quiz' => $quiz,
        'topic' => $quiz->topic,
        'questions' => $quiz->questions,
    ]);
}

public function submitQuiz(Request $request)
{
    $data = $request->validate([
        'quiz_id' => ['required', 'integer', 'exists:quizzes,quiz_id'],
        'answers' => ['required', 'array'],
    ]);

    $userId = auth()->user()->user_id;

    $quiz = Quiz::with('topic')
        ->where('quiz_id', $data['quiz_id'])
        ->firstOrFail();

    if (! $quiz->topic) {
        return redirect()->route('student.quiz');
    }

    if (! $quiz->topic->isUnlockedFor($userId)) {
        return redirect()->route('student.learning');
    }

    $questions = Question::where('quiz_id', $quiz->quiz_id)
        ->orderBy('question_id', 'asc')
        ->get();

    $totalQuestions = $questions->count();

    if ($totalQuestions < 1) {
        return redirect()
            ->route('student.quiz.take', $quiz->quiz_id)
            ->withErrors([
                'quiz' => 'This quiz does not have questions yet.',
            ]);
    }

    $score = 0;
    $answeredQuestions = 0;

    foreach ($questions as $question) {
        $questionId = $question->question_id;
        $selectedAnswer = $data['answers'][$questionId] ?? '';

        if (in_array($selectedAnswer, ['A', 'B', 'C', 'D'], true)) {
            $answeredQuestions++;
        }

        if ($selectedAnswer === $question->correct_answer) {
            $score++;
        }
    }

    if ($answeredQuestions !== $totalQuestions) {
        return redirect()
            ->route('student.quiz.take', $quiz->quiz_id)
            ->withErrors([
                'answers' => 'Please answer all questions before submitting.',
            ]);
    }

    $percentage = ($score / $totalQuestions) * 100;

    $status = $percentage >= $quiz->passing_mark
        ? 'Pass'
        : 'Fail';

    // Idempotent XP reward policy to prevent repeat farming
    $hasPassedBefore = Result::where('user_id', $userId)
        ->where('quiz_id', $quiz->quiz_id)
        ->where('status', 'Pass')
        ->exists();

    $hasAttemptedBefore = Result::where('user_id', $userId)
        ->where('quiz_id', $quiz->quiz_id)
        ->exists();

    if ($status === 'Pass') {
        $pointsEarned = $hasPassedBefore ? 0 : 100;
    } else {
        $pointsEarned = $hasAttemptedBefore ? 0 : 20;
    }

    $result = Result::create([
        'user_id' => $userId,
        'quiz_id' => $quiz->quiz_id,
        'score' => $score,
        'total_questions' => $totalQuestions,
        'percentage' => $percentage,
        'status' => $status,
        'points_earned' => $pointsEarned,
        'date_taken' => now(),
    ]);

    /*
     * Update progress.
     * Passing completes the topic.
     * Failing gives partial progress only if not already completed.
     */
    $progress = Progress::firstOrNew([
        'user_id' => $userId,
        'topic_id' => $quiz->topic_id,
    ]);

    if ($status === 'Pass') {
        $progress->learning_status = 'Completed';
        $progress->quiz_status = 'Passed';
        $progress->progress_percentage = 100;
    } else {
        if ($progress->learning_status !== 'Completed') {
            $progress->learning_status = 'In Progress';
            $progress->quiz_status = 'Failed';

            if ((int) $progress->progress_percentage < 50) {
                $progress->progress_percentage = 50;
            }
        }
    }

    $progress->save();

    // Trigger honors evaluation
    \App\Services\AchievementService::checkAndAward($userId);

return redirect()->route('student.result', $result->result_id);
}

public function result(Result $result)
{
    $userId = auth()->user()->user_id;

    if ((int) $result->user_id !== (int) $userId) {
        return redirect()->route('student.dashboard');
    }

    $result->load('quiz');

    if (! $result->quiz) {
        return redirect()->route('student.dashboard');
    }

    return view('student.result', [
        'pageTitle' => 'Quiz Result',
        'activePage' => 'quiz',
        'result' => $result,
        'quiz' => $result->quiz,
        'isPass' => $result->status === 'Pass',
    ]);
}

    public function progress()
    {
        $userId = auth()->user()->user_id;

        // Aggregate Results in one query
        $resultStats = Result::where('user_id', $userId)
            ->selectRaw("COALESCE(SUM(points_earned), 0) as total_points, COUNT(*) as total_attempts, COALESCE(SUM(CASE WHEN status = 'Pass' THEN 1 ELSE 0 END), 0) as passed_attempts, AVG(percentage) as avg_score")
            ->first();

        $quizPoints     = (int) ($resultStats->total_points    ?? 0);
        $pblPoints      = (int) Progress::where('user_id', $userId)->sum('total_xp');
        $totalPoints    = $quizPoints + $pblPoints;
        $totalAttempts  = (int) ($resultStats->total_attempts  ?? 0);
        $passedAttempts = (int) ($resultStats->passed_attempts ?? 0);
        $averageScore   = (float) ($resultStats->avg_score     ?? 0);
        $passRate       = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100) : 0;

        $rankInfo = RankProgressionService::getRankTelemetry($totalPoints);

        $completedTopics = Progress::where('user_id', $userId)
            ->where('learning_status', 'Completed')
            ->count();

        $completedMissions = UserMissionProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $overallProgress = (float) (Progress::where('user_id', $userId)->avg('progress_percentage') ?? 0);

        $topicProgress = Topic::query()
            ->visibleToStudents()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->withCount(['missions' => function ($q) {
                $q->where('status', 'published');
            }])
            ->select(
                'topics.topic_id',
                'topics.title',
                'topics.description',
                'topics.topic_order',
                'progress.learning_status',
                'progress.quiz_status',
                'progress.progress_percentage',
                'progress.completed_missions',
                'progress.total_xp'
            )
            ->orderBy('topics.topic_order', 'asc')
            ->get();

        $recentResults = Result::query()
            ->join('quizzes', 'results.quiz_id', '=', 'quizzes.quiz_id')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->where('results.user_id', $userId)
            ->select(
                'results.result_id',
                'results.score',
                'results.total_questions',
                'results.percentage',
                'results.status',
                'results.points_earned',
                'results.date_taken',
                'quizzes.title as quiz_title',
                'topics.title as topic_title',
                'topics.topic_id'
            )
            ->orderByDesc('results.date_taken')
            ->limit(5)
            ->get();

        // Actionable "Review Recommended" list: quizzes or topics with low scores or failed attempts
        $recommendedReviews = Result::query()
            ->join('quizzes', 'results.quiz_id', '=', 'quizzes.quiz_id')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->where('results.user_id', $userId)
            ->where(function ($q) {
                $q->where('results.status', 'Fail')
                  ->orWhere('results.percentage', '<', 70);
            })
            ->select(
                'quizzes.quiz_id',
                'quizzes.title as quiz_title',
                'topics.topic_id',
                'topics.title as topic_title',
                'results.percentage',
                'results.status',
                'results.date_taken'
            )
            ->orderBy('results.percentage', 'asc')
            ->limit(3)
            ->get();

        $achievements = UserAchievement::where('user_id', $userId)
            ->with('achievement')
            ->orderByDesc('date_earned')
            ->get();

        return view('student.progress', [
            'pageTitle' => 'My Progress',
            'activePage' => 'progress',
            'totalPoints' => $totalPoints,
            'totalAttempts' => $totalAttempts,
            'passedAttempts' => $passedAttempts,
            'averageScore' => $averageScore,
            'passRate' => $passRate,
            'completedTopics' => $completedTopics,
            'completedMissions' => $completedMissions,
            'overallProgress' => $overallProgress,
            'topicProgress' => $topicProgress,
            'recentResults' => $recentResults,
            'recommendedReviews' => $recommendedReviews,
            'achievements' => $achievements,
            'rankInfo' => $rankInfo,
        ]);
    }

    /**
     * IDs of visible topics the student may open, following the same rule as Topic::isUnlockedFor().
     */
    private function unlockedTopicIds(int $userId): array
    {
        $completedTopicIds = Progress::where('user_id', $userId)
            ->where('learning_status', 'Completed')
            ->pluck('topic_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $unlocked = [];
        $previousCompleted = true;

        foreach (Topic::visibleToStudents()->orderBy('topic_order', 'asc')->pluck('topic_id') as $topicId) {
            if ($previousCompleted) {
                $unlocked[] = (int) $topicId;
            }
            $previousCompleted = in_array((int) $topicId, $completedTopicIds, true);
        }

        return $unlocked;
    }

    /**
     * Dedicated Achievements & Badges Showcase.
     */
    public function achievements()
    {
        $userId = auth()->user()->user_id;

        $quizPoints = (int) Result::where('user_id', $userId)->sum('points_earned');
        $pblPoints = (int) Progress::where('user_id', $userId)->sum('total_xp');
        $totalXp = $quizPoints + $pblPoints;

        $completedTopics = Progress::where('user_id', $userId)
            ->where('learning_status', 'Completed')
            ->count();

        $completedMissions = UserMissionProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $userAchievements = UserAchievement::where('user_id', $userId)
            ->pluck('date_earned', 'achievement_id')
            ->toArray();

        $allAchievements = Achievement::all()->map(function ($ach) use ($userAchievements, $totalXp, $completedTopics, $completedMissions) {
            $isEarned = isset($userAchievements[$ach->achievement_id]);
            $dateEarned = $isEarned ? $userAchievements[$ach->achievement_id] : null;

            $currentVal = 0;
            if ($ach->requirement_type === 'xp_earned') {
                $currentVal = $totalXp;
            } elseif ($ach->requirement_type === 'topics_completed') {
                $currentVal = $completedTopics;
            } elseif ($ach->requirement_type === 'missions_completed') {
                $currentVal = $completedMissions;
            }

            $reqVal = (int) $ach->requirement_value;
            $progressPct = $reqVal > 0 ? min(100, (int) round(($currentVal / $reqVal) * 100)) : 100;

            $status = $isEarned ? 'Earned' : ($progressPct > 0 ? 'In Progress' : 'Locked');

            return [
                'id' => $ach->achievement_id,
                'name' => $ach->badge_name,
                'description' => $ach->description,
                'badge_icon' => $ach->badge_icon ?? 'fa-award',
                'requirement_type' => $ach->requirement_type,
                'requirement_value' => $reqVal,
                'current_value' => $currentVal,
                'progress_pct' => $progressPct,
                'status' => $status,
                'is_earned' => $isEarned,
                'date_earned' => $dateEarned,
            ];
        });

        $earnedCount = $allAchievements->where('is_earned', true)->count();
        $totalCount = $allAchievements->count();

        return view('student.achievements', [
            'pageTitle' => 'Achievements & Honors',
            'activePage' => 'achievements',
            'achievements' => $allAchievements,
            'earnedCount' => $earnedCount,
            'totalCount' => $totalCount,
            'totalXp' => $totalXp,
        ]);
    }
}