@extends('layouts.app')

@section('content')
    <a class="breadcrumb-link" href="{{ route('lecturer.quizzes.index') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Manage Quizzes
    </a>

    <section class="page-banner">
        <p class="eyebrow">Question Management</p>
        <h1>Manage Quiz Questions</h1>
        <p>
            Quiz:
            <strong style="color: var(--cyan);">
                {{ $quiz->title }}
            </strong>
            @if ($quiz->topic)
                for topic
                <strong style="color: var(--cyan);">
                    {{ $quiz->topic->title }}
                </strong>
            @endif
        </p>
    </section>

    @if (session('success'))
        <div class="message success" style="margin-bottom: 1.5rem;">
            <p style="margin-bottom: 0;">
                {{ session('success') }}
            </p>
        </div>
    @endif

    <section class="management-card">
        <div class="stage-meta" style="margin-bottom: 1.5rem;">
            <div>
                <p class="eyebrow">Question List</p>
                <h2 style="margin-bottom: 0;">
                    Available Questions
                </h2>
            </div>

            <a
                class="button"
                href="{{ route('lecturer.questions.create', $quiz->quiz_id) }}"
            >
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Add Question
            </a>
        </div>

        @if ($questions->count() > 0)
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Question</th>
                            <th>Correct Answer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($questions as $index => $question)
                            <tr>
                                <td>
                                    {{ $index + 1 }}
                                </td>

                                <td>
                                    <strong>{{ $question->question_text }}</strong>

                                    <div style="margin-top: 0.75rem; color: var(--text-muted); font-size: 0.9rem;">
                                        <p>A. {{ $question->option_a }}</p>
                                        <p>B. {{ $question->option_b }}</p>
                                        <p>C. {{ $question->option_c }}</p>
                                        <p>D. {{ $question->option_d }}</p>
                                    </div>
                                </td>

                                <td>
                                    <span class="status-badge status-pass">
                                        {{ $question->correct_answer }}
                                    </span>
                                </td>

                                <td>
                                    <div class="actions">
                                        <a
                                            class="button secondary"
                                            href="{{ route('lecturer.questions.edit', [$quiz->quiz_id, $question->question_id]) }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('lecturer.questions.destroy', [$quiz->quiz_id, $question->question_id]) }}"
                                            onsubmit="return confirm('Are you sure you want to delete this question?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="danger-button">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-list-check"></i>
                </div>

                <h2>No questions available</h2>

                <p>
                    Add questions to make this quiz available for students.
                </p>

                <a
                    class="button"
                    href="{{ route('lecturer.questions.create', $quiz->quiz_id) }}"
                >
                    Add First Question
                </a>
            </section>
        @endif
    </section>
@endsection