@extends('layouts.app')

@section('content')
<!-- Top Analytics Widgets -->
<div class="widget-grid" style="margin-bottom: 24px;">
    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(34, 211, 238, 0.1); color: var(--nl-cyan); border-color: rgba(34, 211, 238, 0.25);">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Total Operatives</span>
            <strong style="font-size: 1.4rem; font-weight: 700; color: #FFFFFF;">{{ (int) $totalStudents }}</strong>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.3);">
            <i class="fa-solid fa-diagram-project"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Curriculum Stages</span>
            <strong style="font-size: 1.4rem; font-weight: 700; color: #FFFFFF;">{{ (int) $totalTopics }}</strong>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Evaluations Taken</span>
            <strong style="font-size: 1.4rem; font-weight: 700; color: #FFFFFF;">{{ (int) $totalAttempts }}</strong>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);">
            <i class="fa-solid fa-gauge-high"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Average Convergence</span>
            <strong style="font-size: 1.4rem; font-weight: 700; color: #FFFFFF;">{{ number_format((float) $averageScore, 0) }}%</strong>
        </div>
    </div>
</div>

<!-- 3-Pane Finder Analytics Container -->
<div class="finder-container">
    <!-- Pane 2: Master List View of Topics / Recent Attempts -->
    <div class="finder-pane-master">
        <div class="finder-search-box">
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 6px;">Curriculum Finder</span>
            <input
                type="text"
                id="finderSearchInput"
                class="finder-input"
                placeholder="Filter stages or recent records..."
            >
        </div>

        <div class="finder-list-scroll">
            <div style="padding: 6px 12px; font-size: 0.72rem; text-transform: uppercase; color: var(--nl-text-tertiary); font-weight: 700; letter-spacing: 0.08em;">
                Stages & Telemetry
            </div>

            @foreach ($topicPerformance as $idx => $t)
                <div
                    class="finder-list-item {{ $idx === 0 ? 'active' : '' }}"
                    data-target-detail="topic-detail-{{ $idx }}"
                >
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(255, 255, 255, 0.05); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: var(--nl-cyan); flex-shrink: 0;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <div style="font-size: 0.86rem; font-weight: 600; color: #FFFFFF; text-overflow: ellipsis; overflow: hidden;">
                                {{ $t->topic_title }}
                            </div>
                            <div style="font-size: 0.72rem; color: var(--nl-text-tertiary);">
                                {{ (int) $t->total_attempts }} evaluation attempts
                            </div>
                        </div>
                    </div>
                    <span class="status-pill {{ $t->average_percentage >= 70 ? 'pass' : 'fail' }}" style="flex-shrink: 0;">
                        {{ round($t->average_percentage) }}%
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Pane 3: Detail View of Analytics & Charts -->
    <div class="finder-pane-detail">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--nl-glass-border); padding-bottom: 16px;">
            <div>
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Inspector Telemetry</span>
                <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                    System Performance Analytics
                </h2>
            </div>
            <div style="display: flex; gap: 8px;">
                <a class="button primary" href="{{ route('lecturer.missions.index') }}" style="font-size: 0.82rem; padding: 8px 16px;">
                    <i class="fa-solid fa-puzzle-piece"></i>
                    <span>Missions (PBL)</span>
                </a>
                <a class="button secondary" href="{{ route('lecturer.student-performance.index') }}" style="font-size: 0.82rem; padding: 8px 16px;">
                    <i class="fa-solid fa-users-viewfinder"></i>
                    <span>All Records</span>
                </a>
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

        <!-- Telemetry Charts in Constrained Wrappers -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="chart-card">
                <span class="eyebrow" style="font-size: 0.7rem; color: var(--nl-cyan); margin-bottom: 10px;">Pass / Fail Ratio</span>
                <div class="chart-wrapper-nl">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <span class="eyebrow" style="font-size: 0.7rem; color: var(--nl-cyan); margin-bottom: 10px;">Convergence by Stage</span>
                <div class="chart-wrapper-nl">
                    <canvas id="topicPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Borderless Recent Attempts Table -->
        <div style="margin-top: 8px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0;">Latest Student Attempts</h3>
                <span style="font-size: 0.75rem; color: var(--nl-text-tertiary);">Borderless Glass View</span>
            </div>

            @if ($recentResults->count() > 0)
                <table class="table-borderless-glass">
                    <thead>
                        <tr>
                            <th>Operative</th>
                            <th>Stage & Quiz</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentResults as $result)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #FFFFFF; font-size: 0.88rem;">
                                        {{ $result->student_name }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--nl-text-tertiary);">
                                        {{ $result->student_email }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; font-size: 0.85rem; color: var(--nl-cyan);">
                                        {{ $result->quiz_title }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--nl-text-tertiary);">
                                        {{ $result->topic_title }}
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: #FFFFFF; font-size: 0.9rem;">
                                        {{ (int) $result->score }}/{{ (int) $result->total_questions }}
                                    </strong>
                                    <span style="font-size: 0.75rem; color: var(--nl-text-secondary); margin-left: 4px;">
                                        ({{ round($result->percentage) }}%)
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill {{ $result->status === 'Pass' ? 'pass' : 'fail' }}">
                                        {{ $result->status }}
                                    </span>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--nl-text-secondary);">
                                    {{ $result->date_taken }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="text-align: center; padding: 32px; color: var(--nl-text-tertiary);">
                    No evaluations submitted yet.
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
