<?php

namespace App\Http\Controllers;

use App\Models\Progress;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Topic;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->user()->user_id;

        $totalPoints = Result::where('user_id', $userId)
            ->sum('points_earned');

        $overallProgress = Progress::where('user_id', $userId)
            ->avg('progress_percentage');

        $completedTopics = Progress::where('user_id', $userId)
            ->where('learning_status', 'Completed')
            ->count();

        $overallProgress = $overallProgress ?? 0;

        return view('student.dashboard', [
            'pageTitle' => 'Student Dashboard',
            'activePage' => 'dashboard',
            'totalPoints' => $totalPoints,
            'overallProgress' => $overallProgress,
            'completedTopics' => $completedTopics,
        ]);
    }

    public function learning()
    {
        $userId = auth()->user()->user_id;

        $topics = Topic::query()
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
                'progress.progress_percentage'
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

    /*
     * Check whether this topic is unlocked.
     * The first topic is always available.
     * Later topics require the previous topic to be completed.
     */
    $isUnlocked = (int) $topic->topic_order === 1;

    if (! $isUnlocked) {
        $previousTopic = Topic::query()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->where('topics.topic_order', '<', $topic->topic_order)
            ->orderByDesc('topics.topic_order')
            ->select(
                'topics.topic_id',
                'progress.learning_status'
            )
            ->first();

        $isUnlocked =
            $previousTopic &&
            $previousTopic->learning_status === 'Completed';
    }

    if (! $isUnlocked) {
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

    return view('student.topic', [
        'pageTitle' => 'Learning Topic',
        'activePage' => 'learning',
        'topic' => $topic,
        'quiz' => $quiz,
    ]);
}

public function quizHub()
{
    $userId = auth()->user()->user_id;

    $quizzes = Quiz::query()
        ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
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

    /*
     * Check whether the quiz topic is unlocked.
     */
    $isUnlocked = (int) $quiz->topic->topic_order === 1;

    if (! $isUnlocked) {
        $previousTopic = Topic::query()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->where('topics.topic_order', '<', $quiz->topic->topic_order)
            ->orderByDesc('topics.topic_order')
            ->select(
                'topics.topic_id',
                'progress.learning_status'
            )
            ->first();

        $isUnlocked =
            $previousTopic &&
            $previousTopic->learning_status === 'Completed';
    }

    if (! $isUnlocked) {
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

    /*
     * Prevent submission for locked topics.
     */
    $isUnlocked = (int) $quiz->topic->topic_order === 1;

    if (! $isUnlocked) {
        $previousTopic = Topic::query()
            ->leftJoin('progress', function ($join) use ($userId) {
                $join->on('topics.topic_id', '=', 'progress.topic_id')
                    ->where('progress.user_id', '=', $userId);
            })
            ->where('topics.topic_order', '<', $quiz->topic->topic_order)
            ->orderByDesc('topics.topic_order')
            ->select(
                'topics.topic_id',
                'progress.learning_status'
            )
            ->first();

        $isUnlocked =
            $previousTopic &&
            $previousTopic->learning_status === 'Completed';
    }

    if (! $isUnlocked) {
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

    $pointsEarned = $status === 'Pass'
        ? 100
        : 20;

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

$message = "<b>NetLearn Quiz Result</b>\n\n"
    . "Student: " . auth()->user()->name . "\n"
    . "Quiz: " . $quiz->title . "\n"
    . "Topic: " . $quiz->topic->title . "\n"
    . "Score: " . $score . "/" . $totalQuestions . "\n"
    . "Percentage: " . number_format($percentage, 0) . "%\n"
    . "Status: " . $status . "\n"
    . "XP Earned: +" . $pointsEarned . " XP";

app(TelegramService::class)->sendMessage($message);

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

    $totalPoints = Result::where('user_id', $userId)
        ->sum('points_earned');

    $totalAttempts = Result::where('user_id', $userId)
        ->count();

    $passedAttempts = Result::where('user_id', $userId)
        ->where('status', 'Pass')
        ->count();

    $completedTopics = Progress::where('user_id', $userId)
        ->where('learning_status', 'Completed')
        ->count();

    $overallProgress = Progress::where('user_id', $userId)
        ->avg('progress_percentage');

    $overallProgress = $overallProgress ?? 0;

    $topicProgress = Topic::query()
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
            'progress.progress_percentage'
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
            'topics.title as topic_title'
        )
        ->orderByDesc('results.date_taken')
        ->limit(5)
        ->get();

    return view('student.progress', [
        'pageTitle' => 'My Progress',
        'activePage' => 'progress',
        'totalPoints' => $totalPoints,
        'totalAttempts' => $totalAttempts,
        'passedAttempts' => $passedAttempts,
        'completedTopics' => $completedTopics,
        'overallProgress' => $overallProgress,
        'topicProgress' => $topicProgress,
        'recentResults' => $recentResults,
    ]);
}
}