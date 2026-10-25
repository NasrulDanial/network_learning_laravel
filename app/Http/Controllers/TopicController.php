<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index()
    {
        $topics = Topic::orderBy('topic_order', 'asc')
            ->get();

        return view('lecturer.topics.index', [
            'pageTitle' => 'Manage Topics',
            'activePage' => 'topics',
            'topics' => $topics,
        ]);
    }

    public function create()
    {
        return view('lecturer.topics.create', [
            'pageTitle' => 'Add Topic',
            'activePage' => 'topics',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'topic_order' => ['required', 'integer', 'min:1'],
        ]);

        Topic::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'content' => $data['content'],
            'topic_order' => $data['topic_order'],
            'created_by' => auth()->user()->user_id,
        ]);

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Topic added successfully.');
    }

    public function edit(Topic $topic)
    {
        return view('lecturer.topics.edit', [
            'pageTitle' => 'Edit Topic',
            'activePage' => 'topics',
            'topic' => $topic,
        ]);
    }

    public function update(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'topic_order' => ['required', 'integer', 'min:1'],
        ]);

        $topic->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'content' => $data['content'],
            'topic_order' => $data['topic_order'],
        ]);

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Topic updated successfully.');
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();

        return redirect()
            ->route('lecturer.topics.index')
            ->with('success', 'Topic deleted successfully.');
    }
}