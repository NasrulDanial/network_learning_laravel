<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Topic;
use App\Models\Puzzle;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();

        $quizzes = Quiz::with(['topic', 'puzzle'])
            ->withCount('questions')
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
            'scenario'        => ['nullable', 'string'],
            'puzzle_category' => ['nullable', 'string'],
            'xp_points'       => ['nullable', 'integer', 'min:25', 'max:1000'],
        ]);

        $quiz = Quiz::create([
            'topic_id'     => $data['topic_id'],
            'title'        => $data['title'],
            'passing_mark' => $data['passing_mark'],
        ]);

        if (!empty($data['puzzle_category'])) {
            $typeMap = [
                'topology' => 'drag_and_drop',
                'routing'  => 'scenario',
                'protocol' => 'sequencing',
                'subnet'   => 'identification',
            ];
            $dbType = $typeMap[$data['puzzle_category']] ?? 'drag_and_drop';

            // Board puzzles live outside missions; never touch authored mission activities
            Puzzle::whereNull('mission_id')->firstOrCreate(
                ['topic_id' => $data['topic_id']],
                [
                    'puzzle_type'    => $dbType,
                    'question_text'  => 'Interactive simulation challenge for ' . $data['title'],
                    'instruction'    => $data['scenario'] ?? 'Complete the network simulation according to laboratory specifications.',
                    'correct_answer' => ['configured' => true],
                    'points'         => $data['xp_points'] ?? 150,
                ]
            );
        }

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
            'scenario'        => ['nullable', 'string'],
            'puzzle_category' => ['nullable', 'string'],
            'xp_points'       => ['nullable', 'integer', 'min:25', 'max:1000'],
        ]);

        $quiz->update([
            'topic_id'     => $data['topic_id'],
            'title'        => $data['title'],
            'passing_mark' => $data['passing_mark'],
        ]);

        if (!empty($data['puzzle_category'])) {
            $typeMap = [
                'topology' => 'drag_and_drop',
                'routing'  => 'scenario',
                'protocol' => 'sequencing',
                'subnet'   => 'identification',
            ];
            $dbType = $typeMap[$data['puzzle_category']] ?? 'drag_and_drop';

            // Board puzzles live outside missions; never retype an authored mission activity
            $puzzle = Puzzle::whereNull('mission_id')->firstOrNew(['topic_id' => $data['topic_id']]);
            $puzzle->puzzle_type = $dbType;
            if (!empty($data['scenario'])) {
                $puzzle->instruction = $data['scenario'];
            }
            if (!empty($data['xp_points'])) {
                $puzzle->points = $data['xp_points'];
            }
            if (empty($puzzle->question_text)) {
                $puzzle->question_text = 'Interactive simulation challenge for ' . $data['title'];
            }
            if (empty($puzzle->correct_answer)) {
                $puzzle->correct_answer = ['configured' => true];
            }
            $puzzle->save();
        }

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