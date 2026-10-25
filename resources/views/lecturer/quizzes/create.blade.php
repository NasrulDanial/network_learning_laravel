@extends('layouts.app')

@section('content')
    <a class="breadcrumb-link" href="{{ route('lecturer.quizzes.index') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Manage Quizzes
    </a>

    <section class="page-banner">
        <p class="eyebrow">Create Quiz</p>
        <h1>Add New Quiz Challenge</h1>
        <p>
            Create a quiz challenge and assign it to a networking topic.
        </p>
    </section>

    @if ($errors->any())
        <div class="message error" style="margin-bottom: 1.5rem;">
            @foreach ($errors->all() as $error)
                <p style="margin-bottom: 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="management-card">
        <form method="POST" action="{{ route('lecturer.quizzes.store') }}">
            @csrf

            <div class="form-group">
                <label for="topic_id">Topic</label>
                <select id="topic_id" name="topic_id" required>
                    <option value="">Select topic</option>

                    @foreach ($topics as $topic)
                        <option
                            value="{{ $topic->topic_id }}"
                            {{ (string) old('topic_id') === (string) $topic->topic_id ? 'selected' : '' }}
                        >
                            Stage {{ $topic->topic_order }} — {{ $topic->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Quiz Title</label>
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="passing_mark">Passing Mark (%)</label>
                    <input
                        id="passing_mark"
                        type="number"
                        name="passing_mark"
                        min="0"
                        max="100"
                        value="{{ old('passing_mark', 50) }}"
                        required
                    >
                </div>
            </div>

            <div class="actions">
                <button type="submit">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Save Quiz
                </button>

                <a class="button secondary" href="{{ route('lecturer.quizzes.index') }}">
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection