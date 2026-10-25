@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <p class="eyebrow">Progress Tracking</p>
        <h1>My Learning Progress</h1>
        <p>
            Review your completed topics, quiz attempts, earned XP,
            and overall networking learning progress.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Player Summary</p>
        <h2 style="margin-bottom: 1.5rem;">Overall Performance</h2>

        <div class="summary-grid">
            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    Total XP
                </span>

                <strong class="stat-value">
                    {{ (int) $totalPoints }} XP
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Completed Topics
                </span>

                <strong class="stat-value">
                    {{ (int) $completedTopics }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    Quiz Attempts
                </span>

                <strong class="stat-value">
                    {{ (int) $totalAttempts }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    Passed Quizzes
                </span>

                <strong class="stat-value">
                    {{ (int) $passedAttempts }}
                </strong>
            </div>
        </div>

        <div class="management-card" style="margin-top: 1.5rem;">
            <div class="stage-meta" style="margin-bottom: 0.75rem;">
                <span class="eyebrow">Overall Progress</span>

                <span class="status-badge status-progress">
                    {{ number_format((float) $overallProgress, 0) }}%
                </span>
            </div>

            <div class="progress-track">
                <div
                    class="progress-fill"
                    style="width: {{ min(100, max(0, (float) $overallProgress)) }}%;"
                ></div>
            </div>
        </div>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Topic Progress</p>
        <h2 style="margin-bottom: 1.5rem;">Learning Stage Status</h2>

        @if ($topicProgress->count() > 0)
            <div class="management-card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th>Topic</th>
                                <th>Learning Status</th>
                                <th>Quiz Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($topicProgress as $topic)
                                @php
                                    $learningStatus = $topic->learning_status ?? 'Not Started';
                                    $quizStatus = $topic->quiz_status ?? 'Not Attempted';
                                    $progressPercentage = $topic->progress_percentage ?? 0;

                                    if ($quizStatus === 'Passed') {
                                        $quizBadgeClass = 'status-badge status-pass';
                                    } elseif ($quizStatus === 'Failed') {
                                        $quizBadgeClass = 'status-badge status-fail';
                                    } else {
                                        $quizBadgeClass = 'status-badge status-progress';
                                    }

                                    if ($learningStatus === 'Completed') {
                                        $learningBadgeClass = 'status-badge status-completed';
                                    } elseif ($learningStatus === 'In Progress') {
                                        $learningBadgeClass = 'status-badge status-progress';
                                    } else {
                                        $learningBadgeClass = 'status-badge status-locked';
                                    }
                                @endphp

                                <tr>
                                    <td>
                                        Stage {{ (int) $topic->topic_order }}
                                    </td>

                                    <td>
                                        <strong>{{ $topic->title }}</strong>
                                        <br>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">
                                            {{ $topic->description }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="{{ $learningBadgeClass }}">
                                            {{ $learningStatus }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="{{ $quizBadgeClass }}">
                                            {{ $quizStatus }}
                                        </span>
                                    </td>

                                    <td style="min-width: 160px;">
                                        <strong>
                                            {{ (int) $progressPercentage }}%
                                        </strong>

                                        <div class="progress-track" style="margin-top: 0.5rem;">
                                            <div
                                                class="progress-fill"
                                                style="width: {{ min(100, max(0, (float) $progressPercentage)) }}%;"
                                            ></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-route"></i>
                </div>

                <h2>No topic progress yet</h2>
                <p>
                    Start the learning journey to begin tracking your progress.
                </p>

                <a class="button" href="{{ route('student.learning') }}">
                    Start Learning
                </a>
            </section>
        @endif
    </section>

    <section>
        <p class="eyebrow">Recent Quiz Results</p>
        <h2 style="margin-bottom: 1.5rem;">Latest Attempts</h2>

        @if ($recentResults->count() > 0)
            <div class="dashboard-grid">
                @foreach ($recentResults as $result)
                    @php
                        $isPass = $result->status === 'Pass';
                    @endphp

                    <article class="card">
                        <span class="card-icon" aria-hidden="true">
                            @if ($isPass)
                                <i class="fa-solid fa-trophy"></i>
                            @else
                                <i class="fa-solid fa-rotate-right"></i>
                            @endif
                        </span>

                        <div class="stage-meta">
                            <span class="eyebrow">
                                {{ $result->topic_title }}
                            </span>

                            <span class="status-badge {{ $isPass ? 'status-pass' : 'status-fail' }}">
                                {{ $result->status }}
                            </span>
                        </div>

                        <h2 style="margin-top: 0.5rem;">
                            {{ $result->quiz_title }}
                        </h2>

                        <div class="result-score" style="font-size: 2.5rem; margin: 1rem 0;">
                            {{ number_format((float) $result->percentage, 0) }}%
                        </div>

                        <div class="challenge-info-grid">
                            <div class="challenge-info">
                                <span>Score</span>
                                <strong>
                                    {{ (int) $result->score }}/{{ (int) $result->total_questions }}
                                </strong>
                            </div>

                            <div class="challenge-info">
                                <span>XP</span>
                                <strong>
                                    +{{ (int) $result->points_earned }}
                                </strong>
                            </div>
                        </div>

                        <a
                            class="button secondary"
                            href="{{ route('student.result', $result->result_id) }}"
                            style="margin-top: 1rem;"
                        >
                            View Result
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>

                <h2>No quiz attempts yet</h2>
                <p>
                    Complete a quiz challenge to see your results here.
                </p>

                <a class="button" href="{{ route('student.quiz') }}">
                    Go to Quiz Hub
                </a>
            </section>
        @endif
    </section>
@endsection