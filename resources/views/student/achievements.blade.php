@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <p class="eyebrow">Honors & Badges</p>
        <h1>Achievements & Recognitions</h1>
        <p>
            Earn combat and tactical honors by mastering networking topologies,
            clearing problem-based missions, and testing your theoretical prowess.
        </p>
    </section>

    {{-- Summary Stats --}}
    <div class="summary-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                Total XP
            </span>
            <strong class="stat-value">{{ number_format($totalXp) }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-medal" aria-hidden="true"></i>
                Badges Unlocked
            </span>
            <strong class="stat-value">{{ $earnedCount }} / {{ $totalCount }}</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                Completion
            </span>
            <strong class="stat-value">
                {{ $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0 }}%
            </strong>
        </div>
    </div>

    {{-- Filter Pills --}}
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="button" onclick="filterBadges('all', this)" style="padding: 6px 14px; font-size: 0.82rem;">
                <i class="fa-solid fa-layer-group"></i>
                All Badges ({{ $totalCount }})
            </button>
            <button type="button" class="button secondary" onclick="filterBadges('earned', this)" style="padding: 6px 14px; font-size: 0.82rem;">
                <i class="fa-solid fa-check-circle" style="color: var(--success);"></i>
                Earned ({{ $earnedCount }})
            </button>
            <button type="button" class="button secondary" onclick="filterBadges('in-progress', this)" style="padding: 6px 14px; font-size: 0.82rem;">
                <i class="fa-solid fa-spinner" style="color: var(--warning);"></i>
                In Progress ({{ $achievements->where('status', 'In Progress')->count() }})
            </button>
            <button type="button" class="button secondary" onclick="filterBadges('locked', this)" style="padding: 6px 14px; font-size: 0.82rem;">
                <i class="fa-solid fa-lock"></i>
                Locked ({{ $achievements->where('status', 'Locked')->count() }})
            </button>
        </div>

        <a href="{{ route('student.learning') }}" class="button secondary" style="padding: 8px 16px; font-size: 0.82rem;">
            <i class="fa-solid fa-rocket"></i>
            Resume Curriculum
        </a>
    </div>

    {{-- Badges Grid --}}
    <div class="dashboard-grid" id="badges-container">
        @forelse($achievements as $ach)
            <article class="card achievement-card" data-status="{{ strtolower(str_replace(' ', '-', $ach['status'])) }}"
                     style="{{ $ach['is_earned'] ? 'border-color: rgba(251, 191, 36, 0.35);' : '' }}">

                {{-- Badge Icon & Status --}}
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px;">
                    <div style="width: 52px; height: 52px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
                        {{ $ach['is_earned']
                            ? 'background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.4); color: #fbbf24; box-shadow: 0 0 15px rgba(251, 191, 36, 0.15);'
                            : ($ach['status'] === 'In Progress'
                                ? 'background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.3); color: var(--cyan);'
                                : 'background: var(--bg-secondary); border: 1px solid var(--border); color: var(--text-secondary); opacity: 0.6;') }}">
                        <i class="fa-solid {{ $ach['badge_icon'] }}"></i>
                    </div>

                    @if($ach['is_earned'])
                        <span class="status-badge status-completed">
                            <i class="fa-solid fa-check-circle"></i> Earned
                        </span>
                    @elseif($ach['status'] === 'In Progress')
                        <span class="status-badge status-progress">
                            <i class="fa-solid fa-spinner"></i> {{ $ach['progress_pct'] }}%
                        </span>
                    @else
                        <span class="status-badge status-locked">
                            <i class="fa-solid fa-lock"></i> Locked
                        </span>
                    @endif
                </div>

                {{-- Title & Description --}}
                <h2 style="font-size: 1.05rem; margin-bottom: 6px;">{{ $ach['name'] }}</h2>
                <p style="font-size: 0.85rem; margin-bottom: 16px;">{{ $ach['description'] }}</p>

                {{-- Criteria & Progress --}}
                <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 6px;">
                        <span style="color: var(--text-secondary);">
                            @if($ach['requirement_type'] === 'xp_earned')
                                XP Target
                            @elseif($ach['requirement_type'] === 'topics_completed')
                                Stages Completed
                            @elseif($ach['requirement_type'] === 'missions_completed')
                                Missions Cleared
                            @else
                                Requirement
                            @endif
                        </span>
                        <strong style="color: {{ $ach['is_earned'] ? '#fbbf24' : 'var(--text-main)' }};">
                            {{ number_format($ach['current_value']) }} / {{ number_format($ach['requirement_value']) }}
                        </strong>
                    </div>

                    <div class="progress-track" style="margin: 6px 0 12px 0;">
                        <div class="progress-fill" style="width: {{ $ach['progress_pct'] }}%;
                            {{ $ach['is_earned'] ? 'background: linear-gradient(90deg, #fbbf24, #f59e0b);' : '' }}"></div>
                    </div>

                    @if($ach['is_earned'] && $ach['date_earned'])
                        <p style="font-size: 0.78rem; color: #fbbf24; margin: 0;">
                            <i class="fa-solid fa-calendar-check"></i>
                            Earned on {{ \Carbon\Carbon::parse($ach['date_earned'])->format('M d, Y') }}
                        </p>
                    @else
                        <p style="font-size: 0.78rem; color: var(--text-secondary); margin: 0;">
                            <i class="fa-solid fa-info-circle"></i>
                            Keep completing modules to unlock this badge.
                        </p>
                    @endif
                </div>
            </article>
        @empty
            <section class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <h2>No badges configured</h2>
                <p>No achievements have been set up in this environment yet.</p>
            </section>
        @endforelse
    </div>

<script>
function filterBadges(status, btn) {
    var cards = document.querySelectorAll('.achievement-card');
    cards.forEach(function(card) {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
@endsection
