<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::query()
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->leftJoin('questions', 'quizzes.quiz_id', '=', 'questions.quiz_id')
            ->select(
                'quizzes.quiz_id',
                'quizzes.title',
                'quizzes.passing_mark',
                'quizzes.topic_id',
                'topics.title as topic_title'
            )
            ->selectRaw('COUNT(questions.question_id) as total_questions')
            ->groupBy(
                'quizzes.quiz_id',
                'quizzes.title',
                'quizzes.passing_mark',
                'quizzes.topic_id',
                'topics.title'
            )
            ->orderBy('topics.topic_order', 'asc')
            ->orderBy('quizzes.quiz_id', 'asc')
            ->get();

        return view('lecturer.quizzes.index', [
            'pageTitle' => 'Manage Quizzes',
            'activePage' => 'quizzes',
            'quizzes' => $quizzes,
        ]);
    }

    public function create()
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();

        return view('lecturer.quizzes.create', [
            'pageTitle' => 'Add Quiz',
            'activePage' => 'quizzes',
            'topics' => $topics,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'topic_id' => ['required', 'integer', 'exists:topics,topic_id'],
            'title' => ['required', 'string', 'max:150'],
            'passing_mark' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        Quiz::create([
            'topic_id' => $data['topic_id'],
            'title' => $data['title'],
            'passing_mark' => $data['passing_mark'],
        ]);

        return redirect()
            ->route('lecturer.quizzes.index')
            ->with('success', 'Quiz added successfully.');
    }

    public function edit(Quiz $quiz)
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();

        return view('lecturer.quizzes.edit', [
            'pageTitle' => 'Edit Quiz',
            'activePage' => 'quizzes',
            'quiz' => $quiz,
            'topics' => $topics,
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'topic_id' => ['required', 'integer', 'exists:topics,topic_id'],
            'title' => ['required', 'string', 'max:150'],
            'passing_mark' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $quiz->update([
            'topic_id' => $data['topic_id'],
            'title' => $data['title'],
            'passing_mark' => $data['passing_mark'],
        ]);

        return redirect()
            ->route('lecturer.quizzes.index')
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()
            ->route('lecturer.quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }
}