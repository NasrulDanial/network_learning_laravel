@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <p class="eyebrow">Student Analytics</p>
        <h1>Student Performance</h1>
        <p>
            Review quiz attempts, student scores, pass or fail status,
            and learning performance across networking topics.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Performance Summary</p>
        <h2 style="margin-bottom: 1.5rem;">Overall Result Overview</h2>

        <div class="summary-grid">
            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    Total Attempts
                </span>

                <strong class="stat-value">
                    {{ (int) $totalAttempts }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Passed Attempts
                </span>

                <strong class="stat-value">
                    {{ (int) $passedAttempts }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                    Failed Attempts
                </span>

                <strong class="stat-value">
                    {{ (int) $failedAttempts }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    Average Score
                </span>

                <strong class="stat-value">
                    {{ number_format((float) $averageScore, 0) }}%
                </strong>
            </div>
        </div>
    </section>

    <section class="management-card" style="margin-bottom: 2rem;">
        <div class="stage-meta" style="margin-bottom: 1.5rem;">
            <div>
                <p class="eyebrow">Search and Filter</p>
                <h2 style="margin-bottom: 0;">Find Student Results</h2>
            </div>
        </div>

        <form method="GET" action="{{ route('lecturer.student-performance.index') }}">
            <div class="form-grid">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Student name, email, topic, or quiz"
                    >
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Pass" {{ $status === 'Pass' ? 'selected' : '' }}>
                            Pass
                        </option>
                        <option value="Fail" {{ $status === 'Fail' ? 'selected' : '' }}>
                            Fail
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="topic_id">Topic</label>
                    <select id="topic_id" name="topic_id">
                        <option value="">All Topics</option>

                        @foreach ($topics as $topic)
                            <option
                                value="{{ $topic->topic_id }}"
                                {{ (string) $topicId === (string) $topic->topic_id ? 'selected' : '' }}
                            >
                                Stage {{ $topic->topic_order }} — {{ $topic->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="actions">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    Apply Filter
                </button>

                <a
                    class="button secondary"
                    href="{{ route('lecturer.student-performance.index') }}"
                >
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="management-card">
        <div class="stage-meta" style="margin-bottom: 1.5rem;">
            <div>
                <p class="eyebrow">Result Records</p>
                <h2 style="margin-bottom: 0;">Student Quiz Attempts</h2>
            </div>
        </div>

        @if ($results->count() > 0)
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Topic</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>XP</th>
                            <th>Date Taken</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($results as $result)
                            <tr>
                                <td>
                                    <strong>{{ $result->student_name }}</strong>
                                    <br>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">
                                        {{ $result->student_email }}
                                    </span>
                                </td>

                                <td>
                                    {{ $result->topic_title }}
                                </td>

                                <td>
                                    {{ $result->quiz_title }}
                                </td>

                                <td>
                                    <strong>
                                        {{ (int) $result->score }}/{{ (int) $result->total_questions }}
                                    </strong>
                                    <br>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">
                                        {{ number_format((float) $result->percentage, 0) }}%
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge {{ $result->status === 'Pass' ? 'status-pass' : 'status-fail' }}">
                                        {{ $result->status }}
                                    </span>
                                </td>

                                <td>
                                    +{{ (int) $result->points_earned }} XP
                                </td>

                                <td>
                                    {{ $result->date_taken }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.5rem;">
                {{ $results->links() }}
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>

                <h2>No performance records found</h2>

                <p>
                    Student quiz attempts will appear here after students submit quizzes.
                </p>
            </section>
        @endif
    </section>
@endsection