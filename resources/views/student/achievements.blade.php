@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header / Banner -->
    <div class="glass-card p-6 lg:p-8 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge-cyber badge-cyber-primary">
                        <i class="fas fa-medal mr-1.5"></i> Honors & Badges
                    </span>
                    <span class="text-xs text-slate-400 font-mono">CADET CLEARANCE TRACK</span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-white mb-2">
                    Achievements & Recognitions
                </h1>
                <p class="text-slate-400 max-w-2xl text-sm leading-relaxed">
                    Earn combat and tactical honors by mastering networking topologies, clearing problem-based missions, and testing your theoretical prowess.
                </p>
            </div>

            <!-- Bento Stats Snapshot -->
            <div class="flex items-center gap-3">
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700/60 text-center min-w-[100px]">
                    <span class="text-xs font-mono uppercase text-slate-400 block mb-1">Total XP</span>
                    <span class="text-xl font-bold text-amber-400 font-mono">{{ number_format($totalXp) }}</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700/60 text-center min-w-[100px]">
                    <span class="text-xs font-mono uppercase text-slate-400 block mb-1">Unlocked</span>
                    <span class="text-xl font-bold text-cyan-400 font-mono">{{ $earnedCount }} / {{ $totalCount }}</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700/60 text-center min-w-[100px]">
                    <span class="text-xs font-mono uppercase text-slate-400 block mb-1">Completion</span>
                    <span class="text-xl font-bold text-emerald-400 font-mono">
                        {{ $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2 p-1 rounded-xl bg-slate-900/60 border border-slate-800" role="tablist" aria-label="Achievement Filter">
            <button type="button" 
                    class="filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all active bg-cyan-500/20 text-cyan-300 border border-cyan-500/30" 
                    data-filter="all"
                    onclick="filterBadges('all', this)">
                <i class="fas fa-layer-group mr-1.5"></i> All Badges ({{ $totalCount }})
            </button>
            <button type="button" 
                    class="filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all text-slate-400 hover:text-white" 
                    data-filter="earned"
                    onclick="filterBadges('earned', this)">
                <i class="fas fa-check-circle mr-1.5 text-emerald-400"></i> Earned ({{ $earnedCount }})
            </button>
            <button type="button" 
                    class="filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all text-slate-400 hover:text-white" 
                    data-filter="in-progress"
                    onclick="filterBadges('in-progress', this)">
                <i class="fas fa-spinner mr-1.5 text-amber-400"></i> In Progress ({{ $achievements->where('status', 'In Progress')->count() }})
            </button>
            <button type="button" 
                    class="filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all text-slate-400 hover:text-white" 
                    data-filter="locked"
                    onclick="filterBadges('locked', this)">
                <i class="fas fa-lock mr-1.5 text-slate-500"></i> Locked ({{ $achievements->where('status', 'Locked')->count() }})
            </button>
        </div>

        <a href="{{ route('student.learning') }}" class="btn-cyber btn-cyber-secondary text-xs">
            <i class="fas fa-rocket mr-1.5"></i> Resume Curriculum
        </a>
    </div>

    <!-- Badges Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="badges-container">
        @forelse($achievements as $ach)
            <div class="glass-card p-6 flex flex-col justify-between transition-all duration-300 achievement-card {{ $ach['is_earned'] ? 'border-amber-500/30 bg-amber-500/5' : 'opacity-85 hover:opacity-100' }}"
                 data-status="{{ strtolower(str_replace(' ', '-', $ach['status'])) }}">
                
                <div>
                    <!-- Badge Icon & Status Header -->
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl border transition-all duration-300
                            {{ $ach['is_earned'] 
                                ? 'bg-gradient-to-br from-amber-500/20 to-yellow-600/30 text-amber-300 border-amber-500/40 shadow-lg shadow-amber-500/10' 
                                : ($ach['status'] === 'In Progress' 
                                    ? 'bg-gradient-to-br from-cyan-500/10 to-blue-600/20 text-cyan-400 border-cyan-500/30' 
                                    : 'bg-slate-800/60 text-slate-500 border-slate-700/60 grayscale') }}">
                            <i class="fas {{ $ach['badge_icon'] }}"></i>
                        </div>

                        <div>
                            @if($ach['is_earned'])
                                <span class="badge-cyber badge-cyber-success text-xs">
                                    <i class="fas fa-check-circle mr-1"></i> Earned
                                </span>
                            @elseif($ach['status'] === 'In Progress')
                                <span class="badge-cyber badge-cyber-warning text-xs">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> {{ $ach['progress_pct'] }}%
                                </span>
                            @else
                                <span class="badge-cyber badge-cyber-muted text-xs">
                                    <i class="fas fa-lock mr-1"></i> Locked
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Title & Description -->
                    <h3 class="text-base font-bold text-white mb-1.5 tracking-tight">
                        {{ $ach['name'] }}
                    </h3>
                    <p class="text-xs text-slate-400 mb-4 line-clamp-2 leading-relaxed">
                        {{ $ach['description'] }}
                    </p>
                </div>

                <!-- Footer / Criteria & Progress Bar -->
                <div class="pt-4 border-t border-slate-800/80">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="text-slate-400 font-mono text-[11px]">
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
                        <span class="font-mono font-semibold {{ $ach['is_earned'] ? 'text-amber-400' : 'text-slate-300' }}">
                            {{ number_format($ach['current_value']) }} / {{ number_format($ach['requirement_value']) }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden mb-3">
                        <div class="h-full rounded-full transition-all duration-500 {{ $ach['is_earned'] ? 'bg-gradient-to-r from-amber-400 to-yellow-500' : ($ach['status'] === 'In Progress' ? 'bg-gradient-to-r from-cyan-500 to-blue-500' : 'bg-slate-700') }}"
                             style="width: {{ $ach['progress_pct'] }}%"></div>
                    </div>

                    @if($ach['is_earned'] && $ach['date_earned'])
                        <p class="text-[11px] text-amber-300/80 font-mono flex items-center gap-1.5">
                            <i class="fas fa-calendar-check text-[10px]"></i>
                            Earned on {{ \Carbon\Carbon::parse($ach['date_earned'])->format('M d, Y') }}
                        </p>
                    @else
                        <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                            <i class="fas fa-info-circle text-[10px]"></i>
                            Keep completing modules to unlock this award
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center glass-card">
                <i class="fas fa-medal text-4xl text-slate-600 mb-3"></i>
                <p class="text-slate-400 text-sm">No badges or achievements have been configured in this environment yet.</p>
            </div>
        @endforelse
    </div>
</div>

<script>
function filterBadges(status, btn) {
    // Update button states
    document.querySelectorAll('.filter-pill').forEach(el => {
        el.classList.remove('active', 'bg-cyan-500/20', 'text-cyan-300', 'border', 'border-cyan-500/30');
        el.classList.add('text-slate-400');
    });
    btn.classList.add('active', 'bg-cyan-500/20', 'text-cyan-300', 'border', 'border-cyan-500/30');
    btn.classList.remove('text-slate-400');

    // Filter cards
    const cards = document.querySelectorAll('.achievement-card');
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
@endsection
