@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <p class="eyebrow">Progress Telemetry</p>
                <h1>Learning Progress & Analytics</h1>
                <p style="margin-bottom: 0;">
                    Comprehensive performance telemetry tracking your topic mastery,
                    mission clearance, assessment precision, and clearance rank growth.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('student.learning') }}" class="button" style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fa-solid fa-rocket"></i> Continue Journey
                </a>
                <a href="{{ route('student.achievements') }}" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fa-solid fa-medal"></i> Badges
                </a>
            </div>
        </div>
    </section>

    {{-- Bento Metric Cards --}}
    <div class="summary-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-bolt" aria-hidden="true" style="color: #fbbf24;"></i>
                Total XP
            </span>
            <strong class="stat-value">{{ number_format($totalPoints) }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                Topics Completed
            </span>
            <strong class="stat-value">{{ $completedTopics }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-puzzle-piece" aria-hidden="true" style="color: var(--purple);"></i>
                Missions Cleared
            </span>
            <strong class="stat-value">{{ $completedMissions }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-bullseye" aria-hidden="true" style="color: var(--success);"></i>
                Average Score
            </span>
            <strong class="stat-value">{{ number_format($averageScore, 1) }}%</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-trophy" aria-hidden="true" style="color: var(--cyan);"></i>
                Pass Rate
            </span>
            <strong class="stat-value">{{ $passRate }}%</strong>
            <span style="font-size: 0.78rem; color: var(--text-secondary); display: block; margin-top: 4px;">
                {{ $passedAttempts }} passed / {{ $totalAttempts }} total
            </span>
        </div>
    </div>

    {{-- Career Rank Progression --}}
    <div class="management-card" style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; border-radius: var(--radius-lg); background: rgba(34, 211, 238, 0.12); border: 1px solid rgba(34, 211, 238, 0.3); color: var(--cyan); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                    <i class="fa-solid fa-shield-alt"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span class="status-badge status-progress" style="font-size: 0.72rem;">TIER {{ $rankInfo['tier'] }}</span>
                        <h2 style="margin: 0; font-size: 1.25rem;">{{ $rankInfo['title'] }}</h2>
                    </div>
                    <p style="margin: 0; font-size: 0.85rem;">{{ $rankInfo['tagline'] }}</p>
                </div>
            </div>

            <div style="min-width: 280px; flex: 0 0 auto;">
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 6px;">
                    <span style="color: var(--text-secondary);">Tier Progression</span>
                    <strong style="color: var(--cyan);">{{ $rankInfo['tierProgressPct'] }}%</strong>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: {{ $rankInfo['tierProgressPct'] }}%;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-secondary); margin-top: 4px;">
                    <span>{{ number_format($rankInfo['currentXp']) }} XP</span>
                    @if($rankInfo['nextTierXp'])
                        <span>Target: {{ number_format($rankInfo['nextTierXp']) }} XP</span>
                    @else
                        <span style="color: #fbbf24;">Apex Clearance Reached</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recommended Concept Reinforcement --}}
    @if(isset($recommendedReviews) && $recommendedReviews->count() > 0)
        <section style="margin-bottom: 2rem;">
            <div class="management-card" style="border-color: rgba(245, 158, 11, 0.3); background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, transparent 100%), var(--bg-card);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                    <h2 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-exclamation-triangle" style="color: var(--warning);"></i>
                        Recommended Review
                    </h2>
                    <span class="status-badge status-progress">
                        {{ $recommendedReviews->count() }} {{ Str::plural('area', $recommendedReviews->count()) }}
                    </span>
                </div>

                <p style="font-size: 0.88rem; margin-bottom: 16px;">
                    Scores below 70% or unpassed evaluations detected. Reviewing these topics will boost your mastery:
                </p>

                <div class="dashboard-grid">
                    @foreach($recommendedReviews as $rec)
                        <article class="card" style="border-color: rgba(245, 158, 11, 0.2);">
                            <p class="eyebrow" style="margin-bottom: 4px;">{{ $rec->topic_title }}</p>
                            <h2 style="font-size: 1.05rem;">{{ $rec->quiz_title }}</h2>

                            <div style="display: flex; align-items: center; gap: 8px; margin: 12px 0;">
                                <span class="status-badge status-locked">
                                    Score: {{ round($rec->percentage) }}%
                                </span>
                                <span style="font-size: 0.78rem; color: var(--text-secondary);">
                                    {{ \Carbon\Carbon::parse($rec->date_taken)->diffForHumans() }}
                                </span>
                            </div>

                            <a href="{{ route('student.quiz.take', $rec->quiz_id) }}" class="button" style="margin-top: auto; padding: 8px 16px; font-size: 0.85rem; width: 100%;">
                                <i class="fa-solid fa-redo"></i> Retake Challenge
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Topic Progress Table --}}
    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Curriculum Stage Breakdown</p>
        <h2 style="margin-bottom: 1.5rem;">Learning Stage Status</h2>

        @if ($topicProgress->count() > 0)
            <div class="management-card">
                <div class="table-wrapper" style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th>Topic</th>
                                <th>Missions</th>
                                <th>Quiz Status</th>
                                <th>Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topicProgress as $topic)
                                @php
                                    $learningStatus = $topic->learning_status ?? 'Not Started';
                                    $quizStatus = $topic->quiz_status ?? 'Not Attempted';
                                    $progressPercentage = $topic->progress_percentage ?? 0;
                                    $missionsCount = $topic->missions_count ?? 0;
                                    $completedMissionsLocal = $topic->completed_missions ?? 0;

                                    $quizBadgeClass = match($quizStatus) {
                                        'Passed' => 'status-badge status-pass',
                                        'Failed' => 'status-badge status-fail',
                                        default => 'status-badge status-progress',
                                    };
                                @endphp
                                <tr>
                                    <td><strong style="color: var(--cyan);">Stage {{ (int) $topic->topic_order }}</strong></td>
                                    <td>
                                        <strong>{{ $topic->title }}</strong>
                                        <br><span style="color: var(--text-secondary); font-size: 0.82rem;">{{ Str::limit($topic->description, 60) }}</span>
                                    </td>
                                    <td>
                                        @if ($missionsCount > 0)
                                            <span class="count-badge" style="padding: 4px 10px;">
                                                <i class="fa-solid fa-puzzle-piece"></i>
                                                {{ $completedMissionsLocal }}/{{ $missionsCount }}
                                            </span>
                                        @else
                                            <span style="color: var(--text-secondary); font-size: 0.82rem;">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $quizBadgeClass }}">{{ $quizStatus }}</span>
                                    </td>
                                    <td style="min-width: 140px;">
                                        <strong>{{ (int) $progressPercentage }}%</strong>
                                        <div class="progress-track" style="margin-top: 6px;">
                                            <div class="progress-fill" style="width: {{ min(100, max(0, (float) $progressPercentage)) }}%;
                                                {{ $progressPercentage == 100 ? 'background: linear-gradient(90deg, var(--success), #34d399);' : '' }}"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('student.topic', $topic->topic_id) }}" class="action-link edit">
                                            <i class="fa-solid fa-folder-open"></i> Open
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-route"></i></div>
                <h2>No topic progress yet</h2>
                <p>Start the learning journey to begin tracking your progress.</p>
                <a class="button" href="{{ route('student.learning') }}">Start Learning</a>
            </section>
        @endif
    </section>

    {{-- Recent Assessment History --}}
    <section>
        <p class="eyebrow">Recent Quiz Results</p>
        <h2 style="margin-bottom: 1.5rem;">Latest Attempts</h2>

        @if ($recentResults->count() > 0)
            <div class="dashboard-grid">
                @foreach ($recentResults as $result)
                    @php $isPass = $result->status === 'Pass'; @endphp
                    <article class="card">
                        <span class="card-icon" aria-hidden="true">
                            <i class="fa-solid {{ $isPass ? 'fa-trophy' : 'fa-rotate-right' }}"></i>
                        </span>

                        <div class="stage-meta">
                            <span class="eyebrow">{{ $result->topic_title }}</span>
                            <span class="status-badge {{ $isPass ? 'status-pass' : 'status-fail' }}">
                                {{ $result->status }}
                            </span>
                        </div>

                        <h2 style="margin-top: 0.5rem;">{{ $result->quiz_title }}</h2>

                        <div class="result-score" style="font-size: 2.5rem; margin: 1rem 0;">
                            {{ number_format((float) $result->percentage, 0) }}%
                        </div>

                        <div class="challenge-info-grid">
                            <div class="challenge-info">
                                <span>Score</span>
                                <strong>{{ (int) $result->score }}/{{ (int) $result->total_questions }}</strong>
                            </div>
                            <div class="challenge-info">
                                <span>XP</span>
                                <strong>+{{ (int) $result->points_earned }}</strong>
                            </div>
                        </div>

                        <a class="button secondary" href="{{ route('student.result', $result->result_id) }}" style="margin-top: 1rem;">
                            View Result
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-puzzle-piece"></i></div>
                <h2>No quiz attempts yet</h2>
                <p>Finish a topic's missions, then take its quiz to see your results here.</p>
                <a class="button" href="{{ route('student.quiz') }}">Go to Topic Quizzes</a>
            </section>
        @endif
    </section>
@endsection
