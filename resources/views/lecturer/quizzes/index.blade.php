@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <p class="eyebrow">Quiz Management</p>
        <h1>Manage Quiz Challenges</h1>
        <p>
            Create, update, and organize quiz challenges for each networking topic.
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
                <p class="eyebrow">Quiz List</p>
                <h2 style="margin-bottom: 0;">Available Quizzes</h2>
            </div>

            <a class="button" href="{{ route('lecturer.quizzes.create') }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Add Quiz
            </a>
        </div>

        @if ($quizzes->count() > 0)
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Topic</th>
                            <th>Passing Mark</th>
                            <th>Questions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($quizzes as $quiz)
                            <tr>
                                <td>
                                    <strong>{{ $quiz->title }}</strong>
                                </td>

                                <td>
                                    {{ $quiz->topic_title }}
                                </td>

                                <td>
                                    <span class="status-badge status-progress">
                                        {{ (int) $quiz->passing_mark }}%
                                    </span>
                                </td>

                                <td>
                                    {{ (int) $quiz->total_questions }}
                                </td>

                                <td>
                                    <div class="actions">
                                        <a
                                            class="button secondary"
                                            href="{{ route('lecturer.quizzes.edit', $quiz->quiz_id) }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                            Edit
                                        </a>

                                        <a
                                            class="button secondary"
                                            href="{{ route('lecturer.questions.index', $quiz->quiz_id) }}"
                                        >
                                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                            Questions
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('lecturer.quizzes.destroy', $quiz->quiz_id) }}"
                                            onsubmit="return confirm('Are you sure you want to delete this quiz? Related questions and results may also be deleted.');"
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
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>

                <h2>No quizzes available</h2>

                <p>
                    Create the first quiz challenge and connect it with a learning topic.
                </p>

                <a class="button" href="{{ route('lecturer.quizzes.create') }}">
                    Add First Quiz
                </a>
            </section>
        @endif
    </section>
@endsection