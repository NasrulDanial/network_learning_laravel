<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();

        $quizzes = Quiz::with('topic')
            ->withCount(['questions', 'results'])
            ->withAvg('results', 'percentage')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.topic_id')
            ->select('quizzes.*')
            ->orderBy('topics.topic_order', 'asc')
            ->orderBy('quizzes.quiz_id', 'asc')
            ->get();

        return view('lecturer.quizzes.index', [
            'pageTitle' => 'Manage Quizzes',
            'activePage' => 'quizzes',
            'quizzes' => $quizzes,
            'topics' => $topics,
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
            'topic_id'        => ['required', 'integer', 'exists:topics,topic_id'],
            'title'           => ['required', 'string', 'max:150'],
            'passing_mark'    => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        Quiz::create([
            'topic_id'     => $data['topic_id'],
            'title'        => $data['title'],
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
            'topic_id'        => ['required', 'integer', 'exists:topics,topic_id'],
            'title'           => ['required', 'string', 'max:150'],
            'passing_mark'    => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $quiz->update([
            'topic_id'     => $data['topic_id'],
            'title'        => $data['title'],
            'passing_mark' => $data['passing_mark'],
        ]);

        return redirect()
            ->route('lecturer.quizzes.index')
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        // Deleting would cascade away student results; keep assessment history intact
        $resultCount = $quiz->results()->count();
        if ($resultCount > 0) {
            return redirect()
                ->route('lecturer.quizzes.index')
                ->withErrors(['quiz' => "\"{$quiz->title}\" has {$resultCount} student result(s) and cannot be deleted. Edit its settings or questions instead."]);
        }

        $quiz->delete();

        return redirect()
            ->route('lecturer.quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }
}