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
use App\Services\TopicProgressService;
use App\Services\XpService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->user()->user_id;

        $quizPoints = (int) Result::where('user_id', $userId)->sum('points_earned');
        $pblPoints = (int) Progress::where('user_id', $userId)->sum('total_xp');
        $totalPoints = XpService::total($userId);

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

            $nextMission = Mission::playable()
                ->where('topic_id', $nextTopic->topic_id)
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
            // withCount must come after select(), otherwise select() discards the count column
            ->withCount(['missions' => function ($q) {
                $q->playable();
            }])
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

    // Opening a topic records it as started; the percentage always comes from the facts
    app(TopicProgressService::class)->recalculate($userId, $topic);

    $quiz = Quiz::where('topic_id', $topic->topic_id)
        ->orderBy('quiz_id', 'asc')
        ->first();

    // Load PBL missions with their published puzzles and student progress
    $missions = Mission::playable()
        ->where('topic_id', $topic->topic_id)
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
        $m->student_stars = $prog ? (int) $prog->best_stars : 0;

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
        $m->student_done_count = $puzzles->where('student_is_completed', true)->count();
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
    // and the quiz itself opens only once every mission of its topic is cleared
    $unlockedTopicIds = $this->unlockedTopicIds($userId);
    $topics = Topic::whereIn('topic_id', $quizzes->pluck('topic_id')->unique())->get()->keyBy('topic_id');
    foreach ($quizzes as $quizItem) {
        $topicOpen = in_array((int) $quizItem->topic_id, $unlockedTopicIds, true);
        $missions = $topics->get($quizItem->topic_id)?->missionsClearedBy($userId) ?? ['done' => 0, 'total' => 0];
        $quizItem->missions_done = $missions['done'];
        $quizItem->missions_total = $missions['total'];
        $quizItem->lock_reason = ! $topicOpen ? 'topic' : ($missions['done'] < $missions['total'] ? 'missions' : null);
        $quizItem->is_unlocked = $quizItem->lock_reason === null;
    }

    return view('student.quiz_hub', [
        'pageTitle' => 'Topic Quizzes',
        'activePage' => 'quiz',
        'quizzes' => $quizzes,
    ]);
}

public function quiz(Quiz $quiz)
{
    $userId = auth()->user()->user_id;

    $quiz->load('topic', 'questions');

    if (! $quiz->topic || ! $quiz->topic->isUnlockedFor($userId)) {
        return redirect()->route('student.quiz');
    }

    if ($locked = self::assessmentLock($quiz, $userId)) {
        return redirect()
            ->route('student.topic', ['topic' => $quiz->topic_id, 'tab' => 'missions'])
            ->with('error', $locked);
    }

    if ($quiz->questions->count() < 1) {
        return redirect()
            ->route('student.quiz')
            ->withErrors([
                'quiz' => 'This quiz does not have questions yet.',
            ]);
    }

    $run = self::quizRun($quiz);
    $byId = $quiz->questions->keyBy('question_id');
    $questions = collect($run['order'])->map(fn ($id) => $byId->get($id))->filter()->values();

    // Answers already checked in this attempt come back locked (e.g. after a reload)
    $checked = [];
    foreach ($questions as $question) {
        if (isset($run['answers'][$question->question_id])) {
            $checked[$question->question_id] = self::quizVerdict($question, $run['answers'][$question->question_id], true, $run);
        }
    }

    return view('student.quiz', [
        'pageTitle' => 'Quiz Challenge',
        'activePage' => 'quiz',
        'quiz' => $quiz,
        'topic' => $quiz->topic,
        'questions' => $questions,
        'optionOrder' => $run['options'],
        'checked' => $checked,
        'lives' => Quiz::lives(),
        'livesLeft' => max(0, Quiz::lives() - (int) $run['wrong']),
        'terminated' => (bool) $run['terminated'],
        'claimableXp' => self::claimableQuizXp($userId, $quiz),
    ]);
}

/**
 * Quiz XP is capped per quiz: a failed first attempt earns the consolation XP, a pass earns
 * whatever is left of the cap, so fail-then-pass totals the same as passing first.
 */
public static function quizPoints(int $userId, Quiz $quiz, string $status, bool $ended = false): int
{
    if ($ended) {
        return 0; // abandoned or out of lives
    }
    $previous = Result::where('user_id', $userId)->where('quiz_id', $quiz->quiz_id);
    $alreadyEarned = (int) (clone $previous)->sum('points_earned');
    $cap = (int) config('netlearn.quiz_pass_xp');

    if ($status === 'Pass') {
        return max(0, $cap - $alreadyEarned);
    }

    return (clone $previous)->exists() ? 0 : min($cap, (int) config('netlearn.quiz_first_attempt_xp'));
}

/** XP still available from this quiz for the header badge. */
public static function claimableQuizXp(int $userId, Quiz $quiz): int
{
    $previous = Result::where('user_id', $userId)->where('quiz_id', $quiz->quiz_id);
    if ((clone $previous)->where('status', 'Pass')->exists()) {
        return 0;
    }

    return max(0, (int) config('netlearn.quiz_pass_xp') - (int) $previous->sum('points_earned'));
}

/** Why the quiz is closed to this student (missions not cleared), or null when it is open. */
private static function assessmentLock(Quiz $quiz, int $userId): ?string
{
    if ($quiz->topic->assessmentUnlockedFor($userId)) {
        return null;
    }
    $m = $quiz->topic->missionsClearedBy($userId);

    return "Clear all {$m['total']} missions to unlock the assessment ({$m['done']}/{$m['total']} done).";
}

private static function quizRunKey(Quiz $quiz): string
{
    return 'quizrun.' . $quiz->quiz_id;
}

/**
 * The current attempt, kept in the session: locked answers, wrong-answer count, whether it
 * ended (out of lives), and a per-attempt question and option order that survives reloads.
 */
private static function quizRun(Quiz $quiz): array
{
    $key = self::quizRunKey($quiz);
    $run = session($key);
    $questionIds = $quiz->questions()->orderBy('question_id')->pluck('question_id')->all();

    if (! is_array($run) || ! isset($run['order']) || array_diff($questionIds, $run['order'])) {
        $seed = random_int(1, PHP_INT_MAX >> 1);
        mt_srand($seed);
        $order = $questionIds;
        shuffle($order);
        $options = [];
        foreach ($order as $id) {
            $keys = ['A', 'B', 'C', 'D'];
            shuffle($keys);
            $options[$id] = $keys; // display order only: each key keeps its own text
        }
        mt_srand();

        $run = [
            'answers' => $run['answers'] ?? [],
            'wrong' => (int) ($run['wrong'] ?? 0),
            'terminated' => (bool) ($run['terminated'] ?? false),
            'seed' => $seed,
            'order' => $order,
            'options' => $options,
            'started_at' => now()->timestamp,
        ];
        session([$key => $run]);
    }

    return $run;
}

/** Verdict for a locked answer. The correct option and explanation are only revealed once locked. */
private static function quizVerdict(Question $question, string $answer, bool $alreadyLocked, array $run): array
{
    return [
        'question_id' => $question->question_id,
        'answer' => $answer,
        'correct' => $answer === $question->correct_answer,
        'correct_answer' => $question->correct_answer,
        'explanation' => $question->explanation,
        'locked' => $alreadyLocked,
        'lives' => Quiz::lives(),
        'lives_left' => max(0, Quiz::lives() - (int) $run['wrong']),
        'terminated' => (bool) $run['terminated'],
    ];
}

public function checkQuizAnswer(Request $request, Quiz $quiz)
{
    $data = $request->validate([
        'question_id' => ['required', 'integer'],
        'answer' => ['required', 'in:A,B,C,D'],
    ]);

    $userId = auth()->user()->user_id;
    $quiz->load('topic');
    if (! $quiz->topic || ! $quiz->topic->isUnlockedFor($userId)) {
        abort(403);
    }
    if ($locked = self::assessmentLock($quiz, $userId)) {
        abort(403, $locked);
    }

    $question = Question::where('quiz_id', $quiz->quiz_id)
        ->where('question_id', $data['question_id'])
        ->firstOrFail();

    $run = self::quizRun($quiz);

    // Once checked, an answer cannot be changed: return the stored verdict
    if (isset($run['answers'][$question->question_id])) {
        return response()->json(self::quizVerdict($question, $run['answers'][$question->question_id], true, $run));
    }

    if ($run['terminated']) {
        return response()->json(['message' => 'Attempt already ended', 'terminated' => true], 409);
    }

    $run['answers'][$question->question_id] = $data['answer'];
    if ($data['answer'] !== $question->correct_answer) {
        $run['wrong']++;
        // Out of lives: the attempt is over (LINK DOWN) and can only be submitted as a Fail
        $run['terminated'] = $run['wrong'] >= Quiz::lives();
    }
    session([self::quizRunKey($quiz) => $run]);

    return response()->json(self::quizVerdict($question, $data['answer'], false, $run));
}

public function submitQuiz(Request $request)
{
    $data = $request->validate([
        'quiz_id' => ['required', 'integer', 'exists:quizzes,quiz_id'],
        'answers' => ['nullable', 'array'],
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

    if ($locked = self::assessmentLock($quiz, $userId)) {
        abort(403, $locked);
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

    // Answers checked during the attempt are locked and are the only ones that count. Posted
    // answers are used only when nothing was checked (the page without JavaScript).
    $runKey = self::quizRunKey($quiz);
    $run = session($runKey, []);
    $answers = ! empty($run['answers']) ? $run['answers'] : ($data['answers'] ?? []);
    $terminated = (bool) ($run['terminated'] ?? false);

    $score = 0;
    $answeredQuestions = 0;

    foreach ($questions as $question) {
        $selectedAnswer = $answers[$question->question_id] ?? '';

        if (in_array($selectedAnswer, ['A', 'B', 'C', 'D'], true)) {
            $answeredQuestions++;
        }

        if ($selectedAnswer === $question->correct_answer) {
            $score++;
        }
    }

    // Out of lives, unanswered questions simply count as wrong
    if (! $terminated && $answeredQuestions !== $totalQuestions) {
        return redirect()
            ->route('student.quiz.take', $quiz->quiz_id)
            ->withErrors([
                'answers' => 'Please answer all questions before submitting.',
            ]);
    }

    $percentage = ($score / $totalQuestions) * 100;

    // LINK DOWN is a Fail even when the score would reach the pass mark
    $status = ! $terminated && $percentage >= $quiz->passing_mark ? 'Pass' : 'Fail';

    $pointsEarned = self::quizPoints($userId, $quiz, $status, $terminated);

    session()->forget($runKey);

    $result = Result::create([
        'user_id' => $userId,
        'quiz_id' => $quiz->quiz_id,
        'score' => $score,
        'total_questions' => $totalQuestions,
        'percentage' => $percentage,
        'status' => $status,
        'ended_reason' => $terminated ? 'link_down' : 'finished',
        'points_earned' => $pointsEarned,
        'date_taken' => now(),
    ]);

    app(TopicProgressService::class)->recalculate($userId, $quiz->topic);

    // Trigger honors evaluation
    \App\Services\AchievementService::checkAndAward($userId);

    return redirect()->route('student.result', $result->result_id);
}

/**
 * Leaving mid-quiz: an attempt with at least one checked answer is recorded as a Fail with the
 * score so far and no XP, so answers cannot be previewed by leaving and starting again.
 */
public function abandonQuiz(Quiz $quiz)
{
    $userId = auth()->user()->user_id;
    $quiz->load('topic');
    if (! $quiz->topic || ! $quiz->topic->isUnlockedFor($userId)) {
        abort(403);
    }

    $runKey = self::quizRunKey($quiz);
    $run = session($runKey, []);

    if (! empty($run['answers'])) {
        $questions = Question::where('quiz_id', $quiz->quiz_id)->get(['question_id', 'correct_answer']);
        $total = max(1, $questions->count());
        $score = $questions->filter(fn ($q) => ($run['answers'][$q->question_id] ?? null) === $q->correct_answer)->count();

        Result::create([
            'user_id' => $userId,
            'quiz_id' => $quiz->quiz_id,
            'score' => $score,
            'total_questions' => $questions->count(),
            'percentage' => ($score / $total) * 100,
            'status' => 'Fail',
            'ended_reason' => 'abandoned',
            'points_earned' => 0,
            'date_taken' => now(),
        ]);
        app(TopicProgressService::class)->recalculate($userId, $quiz->topic);
    }

    session()->forget($runKey);

    return response()->json(['redirect' => route('student.quiz')]);
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
        $totalPoints    = XpService::total($userId);
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
            // withCount must come after select(), otherwise select() discards the count column
            ->withCount(['missions' => function ($q) {
                $q->playable();
            }])
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
     * Mission Run: one continuous full-screen run through a mission's activities.
     * Uses the same access rules as playing a single puzzle.
     */
    public function missionRun(Request $request, Mission $mission)
    {
        $userId = auth()->user()->user_id;
        $mission->load('topic');

        abort_unless(config('netlearn.run_mode'), 404);

        $playable = Mission::playable()->whereKey($mission->mission_id)->exists();
        if (! $playable || ! $mission->topic || ! $mission->topic->isUnlockedFor($userId) || ! $mission->isUnlockedFor($userId)) {
            $target = $mission->topic && $mission->topic->isUnlockedFor($userId)
                ? redirect()->route('student.topic', $mission->topic_id)
                : redirect()->route('student.learning');

            return $target->with('error', 'That mission is not available yet. Complete the previous mission first.');
        }

        $puzzles = $mission->publishedPuzzles()->get();
        $progress = UserPuzzleProgress::where('user_id', $userId)
            ->whereIn('puzzle_id', $puzzles->pluck('puzzle_id'))
            ->get()
            ->keyBy('puzzle_id');

        // A replay starts a clean try for every activity, so stars can improve
        if ($request->boolean('restart')) {
            UserPuzzleProgress::where('user_id', $userId)
                ->whereIn('puzzle_id', $puzzles->pluck('puzzle_id'))
                ->update(['try_attempts' => 0, 'try_hint_used' => false]);
        }

        // Resume at the first unfinished activity; a fully finished mission replays from the start
        $startIndex = $request->boolean('restart') ? 0 : max(0, (int) $puzzles->search(fn ($p) => ! optional($progress->get($p->puzzle_id))->is_completed));
        if ($puzzles->every(fn ($p) => optional($progress->get($p->puzzle_id))->is_completed)) {
            $startIndex = 0;
        }

        $devices = ['pc', 'switch', 'router', 'firewall'];
        $last = $puzzles->count() - 1;
        $runPuzzles = $puzzles->values()->map(function ($p, $i) use ($progress, $devices, $last) {
            $prog = $progress->get($p->puzzle_id);
            return [
                'id' => $p->puzzle_id,
                'url' => route('puzzles.show', $p->puzzle_id) . '?run=1',
                'type' => $p->puzzle_type,
                'title' => $p->question_text,
                'device' => $i === $last ? 'server' : $devices[$i % count($devices)],
                'completed' => (bool) optional($prog)->is_completed,
                'stars' => (int) optional($prog)->best_stars,
                'maxAttempts' => (int) $p->max_attempts,
                'attemptsUsed' => (int) optional($prog)->attempts_count,
            ];
        });

        $totalXp = XpService::total($userId);

        $config = [
            'missionId' => $mission->mission_id,
            'title' => $mission->title,
            'topicTitle' => $mission->topic->title,
            'ticketCode' => $mission->ticket_code ?: '#' . str_pad((string) $mission->mission_id, 4, '0', STR_PAD_LEFT),
            'narrator' => $mission->narrator ?: 'Senior Network Engineer',
            'briefing' => $mission->briefing_text ?: ($mission->objective ?: ($mission->description ?: 'A user ticket has arrived. Complete each task to restore the network.')),
            'debriefText' => $mission->debrief_text ?: 'Ticket resolved. Network services restored.',
            'studentName' => auth()->user()->name,
            'topicUrl' => route('student.topic', $mission->topic_id),
            'summaryUrl' => route('student.mission.summary', $mission->mission_id),
            'restartUrl' => route('student.mission.run', $mission->mission_id) . '?restart=1',
            'ttl' => (int) config('netlearn.ttl_seconds'),
            'rankAtStart' => RankProgressionService::getRankTelemetry($totalXp)['current_rank_title'],
            'puzzles' => $runPuzzles,
            'startIndex' => $startIndex,
        ];

        return view('student.mission_run', [
            'pageTitle' => $mission->title,
            'mission' => $mission,
            'runConfig' => $config,
        ]);
    }

    /** Debrief data for a Mission Run (JSON). */
    public function missionSummary(Mission $mission)
    {
        $userId = auth()->user()->user_id;
        $mission->load('topic');

        if (! $mission->topic || ! $mission->topic->isUnlockedFor($userId) || ! $mission->isUnlockedFor($userId)) {
            return response()->json(['message' => 'Mission not available.'], 403);
        }

        $puzzles = $mission->publishedPuzzles()->get();
        $progress = UserPuzzleProgress::where('user_id', $userId)
            ->whereIn('puzzle_id', $puzzles->pluck('puzzle_id'))
            ->get()
            ->keyBy('puzzle_id');
        $missionProgress = UserMissionProgress::where('user_id', $userId)->where('mission_id', $mission->mission_id)->first();

        $puzzleXp = (int) $progress->sum('xp_awarded');
        $missionXp = $missionProgress && $missionProgress->xp_reward_granted ? (int) $mission->xp_reward : 0;

        $totalXp = XpService::total($userId);
        $rank = RankProgressionService::getRankTelemetry($totalXp);

        $nextMission = Mission::playable()
            ->where('topic_id', $mission->topic_id)
            ->where('mission_order', '>', $mission->mission_order)
            ->orderBy('mission_order')
            ->first();
        $nextAvailable = $nextMission && $nextMission->isUnlockedFor($userId);

        return response()->json([
            'completed' => (bool) optional($missionProgress)->is_completed,
            'stars' => (int) optional($missionProgress)->best_stars,
            'puzzleStars' => $puzzles->map(fn ($p) => (int) optional($progress->get($p->puzzle_id))->best_stars)->values(),
            'hintsUsed' => (int) $progress->sum('hints_used'),
            'totalXpThisMission' => $puzzleXp + $missionXp,
            'totalXp' => $totalXp,
            'rank' => $rank['current_rank_title'],
            'rankShort' => $rank['current_rank_short'],
            'rankProgressPct' => (int) $rank['tier_progress_pct'],
            'nextRankXp' => $rank['next_tier_xp'],
            'nextMission' => $nextAvailable ? [
                'title' => $nextMission->title,
                'runUrl' => route('student.mission.run', $nextMission->mission_id),
            ] : null,
            'topicCompleted' => Progress::where('user_id', $userId)->where('topic_id', $mission->topic_id)->value('learning_status') === 'Completed',
            'ticketCode' => $mission->ticket_code ?: '#' . str_pad((string) $mission->mission_id, 4, '0', STR_PAD_LEFT),
            'debriefText' => $mission->debrief_text ?: 'Ticket resolved. Network services restored.',
        ]);
    }

    /**
     * Dedicated Achievements & Badges Showcase.
     */
    public function achievements()
    {
        $userId = auth()->user()->user_id;

        $quizPoints = (int) Result::where('user_id', $userId)->sum('points_earned');
        $pblPoints = (int) Progress::where('user_id', $userId)->sum('total_xp');
        $totalXp = XpService::total($userId);

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