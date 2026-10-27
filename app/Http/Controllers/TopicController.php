<?php

namespace App\Http\Controllers;

use App\Models\Progress;
use App\Models\Puzzle;
use App\Models\PuzzleAttempt;
use App\Models\Result;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    // Duplicate stage numbers make the unlock sequence ambiguous
    private const ORDER_MESSAGES = [
        'topic_order.unique' => 'Another topic already uses this stage number. Pick a free number, or use "Reorder Stages" on the topics list.',
    ];

    public function index()
    {
        $topics = Topic::withCount([
            'missions',
            'publishedMissions',
            'quizzes'
        ])
        ->orderBy('topic_order', 'asc')
        ->get();

        return view('lecturer.topics.index', [
            'pageTitle' => 'Manage Topics',
            'activePage' => 'topics',
            'topics' => $topics,
        ]);
    }

    public function create()
    {
        $maxOrder = (int) Topic::max('topic_order') + 1;

        return view('lecturer.topics.create', [
            'pageTitle' => 'Add Curriculum Topic',
            'activePage' => 'topics',
            'nextOrder' => $maxOrder,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'objectives' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'topic_order' => ['required', 'integer', 'min:1', Rule::unique('topics', 'topic_order')],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'status' => ['nullable', 'in:draft,published'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp,svg', 'max:2048'],
        ], self::ORDER_MESSAGES);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('topics', 'public');
        }

        Topic::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'content' => $data['content'],
            'topic_order' => $data['topic_order'],
            'difficulty' => $data['difficulty'] ?? 'beginner',
            'status' => $data['status'] ?? 'published',
            'estimated_time_minutes' => $data['estimated_time_minutes'] ?? 30,
            'cover_image' => $coverImagePath,
            'created_by' => auth()->user()->user_id,
        ]);

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Curriculum topic created successfully.');
    }

    public function edit(Topic $topic)
    {
        return view('lecturer.topics.edit', [
            'pageTitle' => 'Edit Topic: ' . $topic->title,
            'activePage' => 'topics',
            'topic' => $topic,
        ]);
    }

    public function update(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'objectives' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'topic_order' => ['required', 'integer', 'min:1', Rule::unique('topics', 'topic_order')->ignore($topic->topic_id, 'topic_id')],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'status' => ['nullable', 'in:draft,published'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp,svg', 'max:2048'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ], self::ORDER_MESSAGES);

        $coverImagePath = $topic->cover_image;

        if ($request->boolean('remove_cover_image') && $coverImagePath) {
            Storage::disk('public')->delete($coverImagePath);
            $coverImagePath = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
            }
            $coverImagePath = $request->file('cover_image')->store('topics', 'public');
        }

        $topic->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'content' => $data['content'],
            'topic_order' => $data['topic_order'],
            'difficulty' => $data['difficulty'] ?? 'beginner',
            'status' => $data['status'] ?? 'published',
            'estimated_time_minutes' => $data['estimated_time_minutes'] ?? 30,
            'cover_image' => $coverImagePath,
        ]);

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Topic updated successfully.');
    }

    /**
     * Atomically reorder all topics in one database transaction without duplicate sequence keys.
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'topic_ids' => ['required', 'array'],
            'topic_ids.*' => ['integer', 'exists:topics,topic_id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['topic_ids'] as $index => $id) {
                Topic::where('topic_id', $id)->update(['topic_order' => $index + 1]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Curriculum topic sequence reordered successfully.',
            ]);
        }

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Curriculum topic sequence reordered successfully.');
    }

    public function toggleStatus(Topic $topic)
    {
        $newStatus = $topic->status === 'published' ? 'draft' : 'published';
        $topic->update(['status' => $newStatus]);

        return redirect()
            ->back()
            ->with('success', "Topic #{$topic->topic_order} status updated to " . ucfirst($newStatus) . ".");
    }

    public function destroy(Topic $topic)
    {
        // Missions, puzzles, quizzes and progress cascade from a topic; refuse rather than erase student history
        $history = $this->studentHistoryCount($topic);
        if ($history > 0) {
            return redirect()
                ->route('lecturer.topics.index')
                ->with('error', "\"{$topic->title}\" has {$history} student progress/attempt record(s) and cannot be deleted. Set it to Draft to hide it from students instead.");
        }

        if ($topic->cover_image) {
            Storage::disk('public')->delete($topic->cover_image);
        }

        $topic->delete();

        // Renumber remaining topics sequentially to avoid gaps
        $remaining = Topic::orderBy('topic_order', 'asc')->get();
        foreach ($remaining as $idx => $t) {
            $t->update(['topic_order' => $idx + 1]);
        }

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Topic deleted and remaining stages re-indexed safely.');
    }

    private function studentHistoryCount(Topic $topic): int
    {
        $quizIds = $topic->quizzes()->pluck('quiz_id');
        $puzzleIds = Puzzle::where('topic_id', $topic->topic_id)
            ->orWhereIn('mission_id', $topic->missions()->pluck('mission_id'))
            ->pluck('puzzle_id');

        return Progress::where('topic_id', $topic->topic_id)->count()
            + Result::whereIn('quiz_id', $quizIds)->count()
            + PuzzleAttempt::whereIn('puzzle_id', $puzzleIds)->count();
    }
}
