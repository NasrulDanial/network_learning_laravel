@extends('layouts.app')

@section('content')
<!-- Header Banner -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <div>
        <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Operative Analytics</span>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Student Performance Telemetry</h1>
        <p style="font-size: 0.88rem; color: var(--nl-text-secondary); margin-top: 4px; margin-bottom: 0;">
            Comprehensive log of student evaluation attempts, convergence rates, and clearance progression.
        </p>
    </div>

    <div style="display: flex; gap: 8px;">
        <a class="button secondary" href="{{ route('lecturer.dashboard') }}" style="font-size: 0.82rem; padding: 8px 16px;">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Dashboard</span>
        </a>
    </div>
</div>

<!-- Summary Stat Mini-Widgets -->
<div class="widget-grid" style="margin-bottom: 24px;">
    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(34, 211, 238, 0.1); color: var(--nl-cyan); border-color: rgba(34, 211, 238, 0.25);">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Total Attempts</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ (int) $totalAttempts }}</strong>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Passed Attempts</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #4ade80;">{{ (int) $passedAttempts }}</strong>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Failed Attempts</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #f87171;">{{ (int) $failedAttempts }}</strong>
        </div>
    </div>

    <div class="widget-stat-mini" style="grid-column: span 3;">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Average Score</span>
            <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ number_format((float) $averageScore, 0) }}%</strong>
        </div>
    </div>
</div>

<!-- Search & Filter Card (Frosted Glass Panel) -->
<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-card); padding: 20px; margin-bottom: 24px;">
    <form method="GET" action="{{ route('lecturer.student-performance.index') }}">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1.5fr auto; gap: 16px; align-items: flex-end;">
            <div>
                <label for="search" style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; margin-bottom: 6px;">
                    Search Operative or Stage
                </label>
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Student name, email, or quiz..."
                    class="finder-input"
                >
            </div>

            <div>
                <label for="status" style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; margin-bottom: 6px;">
                    Status
                </label>
                <select id="status" name="status" class="finder-input">
                    <option value="">All Statuses</option>
                    <option value="Pass" {{ $status === 'Pass' ? 'selected' : '' }}>Pass</option>
                    <option value="Fail" {{ $status === 'Fail' ? 'selected' : '' }}>Fail</option>
                </select>
            </div>

            <div>
                <label for="topic_id" style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; margin-bottom: 6px;">
                    Curriculum Stage
                </label>
                <select id="topic_id" name="topic_id" class="finder-input">
                    <option value="">All Stages</option>
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

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button" style="padding: 10px 18px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Filter</span>
                </button>
                <a class="button secondary" href="{{ route('lecturer.student-performance.index') }}" style="padding: 10px 16px;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Borderless Records Table -->
<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Telemetric Records</span>
            <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                Evaluation Logs
            </h2>
        </div>
        <span style="font-size: 0.8rem; color: var(--nl-text-tertiary);">
            Showing {{ $results->firstItem() ?? 0 }} to {{ $results->lastItem() ?? 0 }} of {{ $results->total() }} records
        </span>
    </div>

    @if ($results->count() > 0)
        <table class="table-borderless-glass">
            <thead>
                <tr>
                    <th>Operative</th>
                    <th>Stage & Challenge</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Awarded XP</th>
                    <th>Date Taken</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
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
                            <div style="font-weight: 500; font-size: 0.86rem; color: var(--nl-cyan);">
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

                        <td>
                            <span style="font-weight: 700; color: #fbbf24; font-size: 0.85rem;">
                                +{{ (int) $result->points_earned }} XP
                            </span>
                        </td>

                        <td style="font-size: 0.8rem; color: var(--nl-text-secondary);">
                            {{ $result->date_taken }}
                        </td>

                        <td>
                            <button
                                type="button"
                                class="button secondary"
                                style="padding: 6px 12px; font-size: 0.75rem;"
                                onclick="togglePopover('popover-{{ $result->result_id }}', this)"
                            >
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Inspect</span>
                            </button>

                            <!-- Contextual Popover for Row Details -->
                            <div id="popover-{{ $result->result_id }}" class="popover-card">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <span class="sheet-badge" style="font-size: 0.68rem;">RESULT #{{ $result->result_id }}</span>
                                    <span style="font-size: 0.75rem; color: var(--nl-text-tertiary);">{{ $result->date_taken }}</span>
                                </div>
                                <div style="font-size: 0.88rem; font-weight: 700; color: #FFFFFF; margin-bottom: 2px;">{{ $result->student_name }}</div>
                                <div style="font-size: 0.78rem; color: var(--nl-cyan); margin-bottom: 8px;">{{ $result->quiz_title }}</div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--nl-text-secondary); border-top: 1px solid var(--nl-glass-border); padding-top: 8px;">
                                    <span>Accuracy: {{ round($result->percentage) }}%</span>
                                    <span>XP: +{{ (int) $result->points_earned }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            {{ $results->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 48px; color: var(--nl-text-tertiary);">
            <i class="fa-solid fa-users-slash" style="font-size: 2rem; margin-bottom: 12px; display: block; opacity: 0.4;"></i>
            <h3 style="font-size: 1.1rem; color: #FFFFFF; margin-bottom: 4px;">No evaluation records match filter</h3>
            <p style="font-size: 0.85rem;">Try adjusting search keywords or resetting filter criteria.</p>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function togglePopover(popoverId, triggerBtn) {
    const allPopovers = document.querySelectorAll('.popover-card');
    const target = document.getElementById(popoverId);

    allPopovers.forEach(p => {
        if (p !== target) p.classList.remove('show');
    });

    if (!target) return;

    if (target.classList.contains('show')) {
        target.classList.remove('show');
    } else {
        const rect = triggerBtn.getBoundingClientRect();
        target.style.top = (rect.bottom + window.scrollY + 6) + 'px';
        target.style.left = Math.max(16, rect.left + window.scrollX - 180) + 'px';
        target.classList.add('show');
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.popover-card') && !e.target.closest('button[onclick^="togglePopover"]')) {
        document.querySelectorAll('.popover-card').forEach(p => p.classList.remove('show'));
    }
});
</script>
@endsection
