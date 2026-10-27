@extends('layouts.app')

@section('content')
<!-- Page Header with Direct Quick Actions -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Faculty Operations</span>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Instructor Mission Control</h1>
        <p style="font-size: 0.88rem; color: var(--nl-text-secondary); margin-top: 4px; margin-bottom: 0;">
            Real-time curriculum oversight, content health telemetry, and operative evaluation tracking.
        </p>
    </div>

    <!-- P1: Quick Actions (Create Topic, Create Mission, View Student Performance) -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a class="button" href="{{ route('lecturer.topics.create') }}" style="padding: 9px 16px; font-size: 0.82rem;">
            <i class="fa-solid fa-plus"></i>
            <span>Create Topic</span>
        </a>
        <a class="button" href="{{ route('lecturer.missions.create') }}" style="padding: 9px 16px; font-size: 0.82rem; background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(59, 130, 246, 0.4)); border-color: rgba(139, 92, 246, 0.6);">
            <i class="fa-solid fa-puzzle-piece"></i>
            <span>Create Mission</span>
        </a>
        <a class="button secondary" href="{{ route('lecturer.student-performance.index') }}" style="padding: 9px 16px; font-size: 0.82rem;">
            <i class="fa-solid fa-users-viewfinder"></i>
            <span>View Performance</span>
        </a>
    </div>
</div>

<!-- 6 Authoritative Summary Metric Bento Cards (Blueprint B1) -->
<div class="widget-grid" style="grid-template-columns: repeat(12, 1fr); margin-bottom: 24px; gap: 16px;">
    <div class="widget-stat-mini" style="grid-column: span 2;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(34, 211, 238, 0.1); color: var(--nl-cyan); border-color: rgba(34, 211, 238, 0.25);">
            <i class="fa-solid fa-diagram-project"></i>
        </div>
        <div>
            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Published Topics</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ (int) $publishedTopics }}</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">of {{ (int) $totalTopics }} total</span>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 2;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.3);">
            <i class="fa-solid fa-puzzle-piece"></i>
        </div>
        <div>
            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Published Missions</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ (int) $publishedMissions }}</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">interactive labs</span>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 2;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Needs Attention</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: {{ count($attentionItems) > 0 ? '#f59e0b' : '#34d399' }};">
                {{ count($attentionItems) }}
            </strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">drafts & empty labs</span>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 2;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3);">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Total Attempts</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ (int) $totalAttempts }}</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">quizzes + puzzles</span>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 2;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">
            <i class="fa-solid fa-gauge-high"></i>
        </div>
        <div>
            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Class Avg Score</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ number_format((float) $averageScore, 0) }}%</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">evaluations accuracy</span>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 2;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(34, 197, 94, 0.15); color: #4ade80; border-color: rgba(34, 197, 94, 0.3);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Class Pass Rate</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #4ade80;">{{ $passRate }}%</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">passing ratio</span>
        </div>
    </div>
</div>

<!-- Main Split Layout: Content Needing Attention (Left) + Analytics & Recent Activity (Right) -->
<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; margin-bottom: 24px; align-items: start;">

    <!-- Left Column: Content Needing Attention (Blueprint B1) -->
    <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #FFFFFF;">Content Needing Attention</h2>
                    <span style="font-size: 0.74rem; color: var(--nl-text-tertiary);">Curriculum health alerts & drafts</span>
                </div>
            </div>
            <span class="status-pill {{ count($attentionItems) > 0 ? 'fail' : 'pass' }}" style="font-size: 0.72rem;">
                {{ count($attentionItems) }} Issues
            </span>
        </div>

        @if (count($attentionItems) > 0)
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($attentionItems as $item)
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; color: {{ $item['color'] }}; flex-shrink: 0;">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                            </div>
                            <div style="overflow: hidden; text-overflow: ellipsis;">
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                    <span style="font-size: 0.65rem; padding: 1px 6px; border-radius: 4px; background: rgba(245, 158, 11, 0.15); color: #fbbf24; font-weight: 700;">
                                        {{ $item['badge'] }}
                                    </span>
                                    <strong style="font-size: 0.85rem; color: #FFFFFF; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        {{ $item['title'] }}
                                    </strong>
                                </div>
                                <p style="font-size: 0.75rem; color: var(--nl-text-secondary); margin: 0; line-height: 1.35;">
                                    {{ $item['reason'] }}
                                </p>
                            </div>
                        </div>

                        <a href="{{ $item['edit_url'] }}" class="button secondary" style="font-size: 0.75rem; padding: 6px 12px; flex-shrink: 0; white-space: nowrap;">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Resolve
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 36px 16px; color: var(--nl-text-tertiary);">
                <i class="fa-solid fa-circle-check" style="font-size: 2.2rem; color: #4ade80; margin-bottom: 10px; display: block;"></i>
                <h3 style="font-size: 0.95rem; color: #FFFFFF; margin-bottom: 4px;">All Curriculum Content Healthy</h3>
                <p style="font-size: 0.8rem; margin: 0;">All published topics and missions contain ready activities. No drafts pending.</p>
            </div>
        @endif

        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--nl-glass-border); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.76rem; color: var(--nl-text-tertiary);">Curriculum management shortcut:</span>
            <a href="{{ route('lecturer.topics.index') }}" style="font-size: 0.78rem; color: var(--nl-cyan); text-decoration: none; font-weight: 600;">
                Manage Curriculum Stages &rarr;
            </a>
        </div>
    </div>

    <!-- Right Column: Visual Charts & Recent Activity Log -->
    <div style="display: flex; flex-direction: column; gap: 20px;">

        <!-- Analytics Charts in Constrained Bento Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="chart-card">
                <span class="eyebrow" style="font-size: 0.7rem; color: var(--nl-cyan); margin-bottom: 8px;">Pass / Fail Telemetry</span>
                <div class="chart-wrapper-nl">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <span class="eyebrow" style="font-size: 0.7rem; color: var(--nl-cyan); margin-bottom: 8px;">Stage Accuracy %</span>
                <div class="chart-wrapper-nl">
                    <canvas id="topicPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Embedded Telemetry Data Store for Chart.js -->
        <script id="lecturerChartData" type="application/json">
        {
            "passFailLabels": @json(array_keys($passFailData)),
            "passFailValues": @json(array_values($passFailData)),
            "topicLabels": @json($topicPerformance->pluck('topic_title')),
            "topicAverages": @json($topicPerformance->pluck('average_percentage')->map(fn ($val) => round($val, 1)))
        }
        </script>

        <!-- Recent Student Activity (Blueprint B1) -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                    Recent Student Activity
                </h3>
                <a href="{{ route('lecturer.student-performance.index') }}" style="font-size: 0.78rem; color: var(--nl-cyan); text-decoration: none; font-weight: 600;">
                    View All Performance Logs &rarr;
                </a>
            </div>

            @if ($recentResults->count() > 0)
                <table class="table-borderless-glass">
                    <thead>
                        <tr>
                            <th>Student Operative</th>
                            <th>Curriculum Activity</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentResults as $result)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #FFFFFF; font-size: 0.86rem;">
                                        <a href="{{ route('lecturer.student-performance.show', $result->user_id) }}" style="color: #FFFFFF; text-decoration: none;">
                                            {{ $result->student_name }}
                                        </a>
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--nl-text-tertiary);">
                                        {{ $result->student_email }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; font-size: 0.84rem; color: var(--nl-cyan);">
                                        {{ $result->quiz_title }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--nl-text-tertiary);">
                                        {{ $result->topic_title }}
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: #FFFFFF; font-size: 0.88rem;">
                                        {{ (int) $result->score }}/{{ (int) $result->total_questions }}
                                    </strong>
                                    <span style="font-size: 0.72rem; color: var(--nl-text-secondary); margin-left: 3px;">
                                        ({{ round($result->percentage) }}%)
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill {{ $result->status === 'Pass' ? 'pass' : 'fail' }}">
                                        {{ $result->status }}
                                    </span>
                                </td>
                                <td style="font-size: 0.78rem; color: var(--nl-text-secondary);">
                                    {{ $result->date_taken }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="text-align: center; padding: 28px; color: var(--nl-text-tertiary);">
                    No evaluation attempts recorded yet.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    if (typeof window.initLecturerCharts === 'function') {
        window.initLecturerCharts();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.initLecturerCharts === 'function') {
                window.initLecturerCharts();
            }
        });
    }
</script>
@endsection
