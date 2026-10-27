<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TopicController extends Controller
{
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
            'topic_order' => ['required', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'status' => ['nullable', 'in:draft,published'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp,svg', 'max:2048'],
        ]);

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
            'topic_order' => ['required', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'in:beginner,intermediate,advanced'],
            'status' => ['nullable', 'in:draft,published'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp,svg', 'max:2048'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ]);

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
}
