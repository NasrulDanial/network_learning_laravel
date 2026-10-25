<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Quiz $quiz)
    {
        $quiz->load('topic');

        $questions = Question::where('quiz_id', $quiz->quiz_id)
            ->orderBy('question_id', 'asc')
            ->get();

        return view('lecturer.questions.index', [
            'pageTitle' => 'Manage Questions',
            'activePage' => 'quizzes',
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    public function create(Quiz $quiz)
    {
        $quiz->load('topic');

        return view('lecturer.questions.create', [
            'pageTitle' => 'Add Question',
            'activePage' => 'quizzes',
            'quiz' => $quiz,
        ]);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
        ]);

        Question::create([
            'quiz_id' => $quiz->quiz_id,
            'question_text' => $data['question_text'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'],
            'option_d' => $data['option_d'],
            'correct_answer' => $data['correct_answer'],
        ]);

        return redirect()
            ->route('lecturer.questions.index', $quiz->quiz_id)
            ->with('success', 'Question added successfully.');
    }

    public function edit(Quiz $quiz, Question $question)
    {
        if ((int) $question->quiz_id !== (int) $quiz->quiz_id) {
            abort(404);
        }

        $quiz->load('topic');

        return view('lecturer.questions.edit', [
            'pageTitle' => 'Edit Question',
            'activePage' => 'quizzes',
            'quiz' => $quiz,
            'question' => $question,
        ]);
    }

    public function update(Request $request, Quiz $quiz, Question $question)
    {
        if ((int) $question->quiz_id !== (int) $quiz->quiz_id) {
            abort(404);
        }

        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
        ]);

        $question->update([
            'question_text' => $data['question_text'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'],
            'option_d' => $data['option_d'],
            'correct_answer' => $data['correct_answer'],
        ]);

        return redirect()
            ->route('lecturer.questions.index', $quiz->quiz_id)
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Quiz $quiz, Question $question)
    {
        if ((int) $question->quiz_id !== (int) $quiz->quiz_id) {
            abort(404);
        }

        $question->delete();

        return redirect()
            ->route('lecturer.questions.index', $quiz->quiz_id)
            ->with('success', 'Question deleted successfully.');
    }
}