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
                    <i class="fa-solid fa-puzzle-piece" aria-hidden="true" style="color: var(--nl-cyan);"></i>
                    Missions Cleared
                </span>

                <strong class="stat-value">
                    {{ (int) ($completedMissions ?? 0) }}
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

    <!-- Operative Badges & Achievements -->
    <section style="margin-bottom: 2rem;">
        <p class="eyebrow" style="color: var(--nl-cyan);">Clearance Honors</p>
        <h2 style="margin-bottom: 1.5rem;">Earned Badges & Honors</h2>

        @if (isset($achievements) && $achievements->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                @foreach ($achievements as $ua)
                    <div style="background: rgba(13, 33, 56, 0.65); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(251, 191, 36, 0.35); border-radius: 16px; padding: 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.4); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fbbf24; flex-shrink: 0; box-shadow: 0 0 15px rgba(251, 191, 36, 0.2);">
                            <i class="fa-solid {{ $ua->achievement->badge_icon ?: 'fa-award' }}"></i>
                        </div>
                        <div style="min-width: 0;">
                            <div style="font-size: 0.95rem; font-weight: 700; color: #FFFFFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $ua->achievement->badge_name }}</div>
                            <div style="font-size: 0.78rem; color: var(--nl-text-secondary); margin-top: 2px; line-height: 1.3;">{{ $ua->achievement->description }}</div>
                            <div style="font-size: 0.72rem; color: #22d3ee; margin-top: 6px; font-weight: 600;">
                                <i class="fa-solid fa-clock-rotate-left"></i> {{ \Carbon\Carbon::parse($ua->date_earned)->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="management-card" style="padding: 24px; text-align: center; color: var(--nl-text-tertiary);">
                <i class="fa-solid fa-award" style="font-size: 2.2rem; margin-bottom: 8px; opacity: 0.4; color: #fbbf24; display: block;"></i>
                <strong style="color: #FFFFFF; font-size: 1rem; display: block; margin-bottom: 4px;">No clearance honors unlocked yet</strong>
                <p style="margin: 0; font-size: 0.85rem; color: var(--nl-text-secondary);">Solve interactive missions, achieve high scores, and advance through curriculum stages to earn badges.</p>
            </div>
        @endif
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
                                <th>Missions</th>
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
                                    $missionsCount = $topic->missions_count ?? 0;
                                    $completedMissions = $topic->completed_missions ?? 0;

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
                                        @if ($missionsCount > 0)
                                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 600; color: #22d3ee; background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.25); border-radius: 8px; padding: 4px 10px;">
                                                <i class="fa-solid fa-puzzle-piece"></i>
                                                {{ $completedMissions }}/{{ $missionsCount }} {{ Str::plural('Mission', $missionsCount) }}
                                            </span>
                                        @else
                                            <span style="color: var(--nl-text-tertiary); font-size: 0.82rem;">None</span>
                                        @endif
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
