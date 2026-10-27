@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <div class="quiz-lib-banner">
            <div>
                <p class="eyebrow">Topic Assessments</p>
                <h1>Manage Quizzes</h1>
                <p>
                    Each quiz is the multiple-choice assessment that completes its topic. Passing it marks the
                    topic as completed and unlocks the next one. Interactive puzzles are managed inside
                    <a href="{{ route('lecturer.missions.index') }}">Missions</a>.
                </p>
            </div>

            <a class="button" href="{{ route('lecturer.quizzes.create') }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Add Quiz</span>
            </a>
        </div>
    </section>

    @if (session('success'))
        <div class="message success" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="message error" role="alert">
            @foreach ($errors->all() as $error)
                <p style="margin-bottom: 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if ($quizzes->count() > 0)
        <div class="quiz-lib-toolbar">
            <label class="visually-hidden" for="quizSearch">Search quizzes</label>
            <input
                id="quizSearch"
                type="search"
                placeholder="Search by quiz or topic title"
                autocomplete="off"
            >
            <span class="quiz-lib-count" id="quizResultCount" aria-live="polite">
                {{ $quizzes->count() }} {{ Str::plural('quiz', $quizzes->count()) }}
            </span>
        </div>

        <section class="management-card quiz-lib-card">
            <div class="table-wrap">
                <table class="table-borderless-glass quiz-lib-table">
                    <thead>
                        <tr>
                            <th scope="col">Quiz</th>
                            <th scope="col">Topic</th>
                            <th scope="col">Questions</th>
                            <th scope="col">Pass mark</th>
                            <th scope="col">Student results</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizzes as $quiz)
                            @php
                                $questionCount = (int) $quiz->questions_count;
                                $resultCount = (int) $quiz->results_count;
                                $topicStatus = $quiz->topic->status ?? 'published';
                            @endphp
                            <tr
                                class="quiz-row"
                                data-search="{{ Str::lower($quiz->title . ' ' . ($quiz->topic->title ?? '')) }}"
                            >
                                <td>
                                    <strong class="quiz-lib-title">{{ $quiz->title }}</strong>
                                    @if ($questionCount === 0)
                                        <span class="status-badge status-warning">
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                            No questions — students cannot take it
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="quiz-lib-topic">
                                        Stage {{ $quiz->topic->topic_order ?? '—' }} · {{ $quiz->topic->title ?? 'Unassigned' }}
                                    </span>
                                    @if ($topicStatus === 'draft')
                                        <span class="status-badge status-draft">
                                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                                            Topic is draft
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $questionCount }}</td>
                                <td>{{ (int) $quiz->passing_mark }}%</td>
                                <td>
                                    @if ($resultCount > 0)
                                        {{ $resultCount }} {{ Str::plural('attempt', $resultCount) }}
                                        <span class="quiz-lib-muted">· avg {{ number_format((float) $quiz->results_avg_percentage, 1) }}%</span>
                                    @else
                                        <span class="quiz-lib-muted">No attempts yet</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group quiz-lib-actions">
                                        <a class="button" href="{{ route('lecturer.questions.index', $quiz->quiz_id) }}">
                                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                            Edit Questions
                                        </a>
                                        <a class="button secondary" href="{{ route('lecturer.quizzes.edit', $quiz->quiz_id) }}">
                                            <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                                            Settings
                                        </a>
                                        @if ($resultCount > 0)
                                            <button
                                                type="button"
                                                class="button secondary"
                                                disabled
                                                title="This quiz has {{ $resultCount }} student {{ Str::plural('result', $resultCount) }} and cannot be deleted."
                                            >
                                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                                Delete
                                            </button>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route('lecturer.quizzes.destroy', $quiz->quiz_id) }}"
                                                onsubmit="return confirm('Delete the quiz “{{ addslashes($quiz->title) }}” and its {{ $questionCount }} {{ Str::plural('question', $questionCount) }}? This cannot be undone.');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button danger">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="empty-inline" id="quizNoMatch" hidden>No quizzes match your search.</p>
        </section>
    @else
        <section class="empty-state">
            <div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-clipboard-question"></i></div>
            <h2>No quizzes yet</h2>
            <p>Add a quiz to a topic so students can complete the topic and unlock the next one.</p>
            <a class="button" href="{{ route('lecturer.quizzes.create') }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Add Quiz
            </a>
        </section>
    @endif
@endsection

@section('scripts')
    <script>
        (function () {
            const input = document.getElementById('quizSearch');
            if (!input) return;

            const rows = Array.from(document.querySelectorAll('.quiz-row'));
            const counter = document.getElementById('quizResultCount');
            const noMatch = document.getElementById('quizNoMatch');

            input.addEventListener('input', function () {
                const term = input.value.trim().toLowerCase();
                let visible = 0;

                rows.forEach(function (row) {
                    const match = term === '' || row.dataset.search.includes(term);
                    row.hidden = !match;
                    if (match) visible++;
                });

                counter.textContent = visible + (visible === 1 ? ' quiz' : ' quizzes');
                noMatch.hidden = visible !== 0;
            });
        })();
    </script>
@endsection
