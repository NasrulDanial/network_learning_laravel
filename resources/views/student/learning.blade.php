@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="glass-card p-6 lg:p-8 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge-cyber badge-cyber-primary">
                        <i class="fas fa-map-marked-alt mr-1.5"></i> Curriculum Path
                    </span>
                    <span class="text-xs text-slate-400 font-mono">PROBLEM-BASED LEARNING</span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-white mb-2">
                    Networking Adventure Journey
                </h1>
                <p class="text-slate-400 max-w-2xl text-sm leading-relaxed">
                    Progress through structured networking milestones. Each stage contains interactive puzzle missions and evaluations. Complete stages sequentially to unlock advanced operations.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('student.quiz') }}" class="btn-cyber btn-cyber-secondary text-xs">
                    <i class="fas fa-bolt mr-1.5"></i> Practice Arena
                </a>
                <a href="{{ route('student.progress') }}" class="btn-cyber btn-cyber-primary text-xs">
                    <i class="fas fa-chart-line mr-1.5"></i> My Progress
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Stats Bar -->
    @if($topics->count() > 0)
        @php
            $completedCount = $topics->where('learning_status', 'Completed')->count();
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 p-1 rounded-xl bg-slate-900/60 border border-slate-800" role="tablist">
                <button type="button" 
                        class="topic-filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all active bg-cyan-500/20 text-cyan-300 border border-cyan-500/30" 
                        data-filter="all"
                        onclick="filterTopics('all', this)">
                    <i class="fas fa-th-large mr-1.5"></i> All Stages ({{ $topics->count() }})
                </button>
                <button type="button" 
                        class="topic-filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all text-slate-400 hover:text-white" 
                        data-filter="in-progress"
                        onclick="filterTopics('in-progress', this)">
                    <i class="fas fa-play-circle mr-1.5 text-cyan-400"></i> Available / Active
                </button>
                <button type="button" 
                        class="topic-filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all text-slate-400 hover:text-white" 
                        data-filter="completed"
                        onclick="filterTopics('completed', this)">
                    <i class="fas fa-check-circle mr-1.5 text-emerald-400"></i> Completed ({{ $completedCount }})
                </button>
                <button type="button" 
                        class="topic-filter-pill px-4 py-2 text-xs font-semibold rounded-lg transition-all text-slate-400 hover:text-white" 
                        data-filter="locked"
                        onclick="filterTopics('locked', this)">
                    <i class="fas fa-lock mr-1.5 text-slate-500"></i> Locked
                </button>
            </div>

            <div class="text-xs font-mono text-slate-400">
                Milestones Cleared: <strong class="text-emerald-400">{{ $completedCount }}</strong> / {{ $topics->count() }}
            </div>
        </div>
    @endif

    <!-- Stages Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="topics-container">
        @if ($topics->count() > 0)
            @php
                $previousCompleted = true;
                $topicNumber = 1;
            @endphp

            @foreach ($topics as $topic)
                @php
                    $isFirst = (int) $topic->topic_order === 1;
                    $isUnlocked = $isFirst || $previousCompleted;

                    $learningStatus = $topic->learning_status ?? 'Not Started';
                    $quizStatus = $topic->quiz_status ?? 'Not Attempted';
                    $progressPercentage = (float) ($topic->progress_percentage ?? 0);
                    $missionsCount = (int) ($topic->missions_count ?? 0);
                    $completedMissions = (int) ($topic->completed_missions ?? 0);
                    $difficulty = $topic->difficulty ?? 'Beginner';
                    $estimatedTime = $topic->estimated_time_minutes ?? 45;

                    $currentCompleted = $learningStatus === 'Completed';

                    // Determine filter status category
                    if ($currentCompleted) {
                        $filterCat = 'completed';
                    } elseif ($isUnlocked) {
                        $filterCat = 'in-progress';
                    } else {
                        $filterCat = 'locked';
                    }

                    // Difficulty badge styling
                    $diffClass = match(strtolower($difficulty)) {
                        'advanced' => 'badge-cyber-danger',
                        'intermediate' => 'badge-cyber-warning',
                        default => 'badge-cyber-success',
                    };
                @endphp

                <div class="glass-card flex flex-col justify-between overflow-hidden transition-all duration-300 topic-card {{ $currentCompleted ? 'border-emerald-500/30' : ($isUnlocked ? 'border-cyan-500/30' : 'opacity-70') }}"
                     data-status="{{ $filterCat }}">
                    
                    <!-- Card Media / Cover Banner -->
                    <div class="h-36 w-full relative overflow-hidden bg-slate-900 flex items-center justify-center">
                        @if($topic->cover_image && file_exists(public_path($topic->cover_image)))
                            <img src="{{ asset($topic->cover_image) }}" alt="{{ $topic->title }}" class="w-full h-full object-cover">
                        @else
                            <!-- Deterministic Geometric Network Graphic -->
                            <div class="w-full h-full bg-gradient-to-br from-slate-900 via-slate-800 to-cyan-950 flex items-center justify-center relative">
                                <svg class="w-full h-full absolute inset-0 opacity-20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                                    <line x1="10" y1="20" x2="50" y2="80" stroke="#22d3ee" stroke-width="0.5" />
                                    <line x1="50" y1="80" x2="90" y2="30" stroke="#8b5cf6" stroke-width="0.5" />
                                    <line x1="90" y1="30" x2="30" y2="30" stroke="#22d3ee" stroke-width="0.5" />
                                    <circle cx="10" cy="20" r="3" fill="#22d3ee" />
                                    <circle cx="50" cy="80" r="4" fill="#8b5cf6" />
                                    <circle cx="90" cy="30" r="3" fill="#22d3ee" />
                                    <circle cx="30" cy="30" r="3" fill="#38bdf8" />
                                </svg>
                                <div class="w-12 h-12 rounded-xl bg-slate-900/80 border border-cyan-500/40 text-cyan-400 flex items-center justify-center text-xl z-10 shadow-lg">
                                    <i class="fas fa-network-wired"></i>
                                </div>
                            </div>
                        @endif

                        <!-- Top Floating Badges -->
                        <div class="absolute top-3 left-3 flex items-center gap-1.5">
                            <span class="badge-cyber badge-cyber-primary text-[10px] font-mono">
                                Stage {{ $topic->topic_order }}
                            </span>
                        </div>

                        <div class="absolute top-3 right-3">
                            @if($currentCompleted)
                                <span class="badge-cyber badge-cyber-success text-[10px]">
                                    <i class="fas fa-check-circle mr-1"></i> Completed
                                </span>
                            @elseif($isUnlocked)
                                <span class="badge-cyber badge-cyber-warning text-[10px]">
                                    <i class="fas fa-play mr-1"></i> Available
                                </span>
                            @else
                                <span class="badge-cyber badge-cyber-muted text-[10px]">
                                    <i class="fas fa-lock mr-1"></i> Locked
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2 text-xs">
                                <span class="badge-cyber {{ $diffClass }} text-[10px]">
                                    {{ ucfirst($difficulty) }}
                                </span>
                                <span class="text-slate-400 font-mono text-[11px]">
                                    <i class="fas fa-clock mr-1 text-slate-500"></i> ~{{ $estimatedTime }}m
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white mb-2 tracking-tight line-clamp-1">
                                {{ $topic->title }}
                            </h3>
                            <p class="text-xs text-slate-400 mb-4 line-clamp-2 leading-relaxed">
                                {{ $topic->description }}
                            </p>
                        </div>

                        <!-- Progress and Action Area -->
                        <div class="pt-4 border-t border-slate-800/80">
                            <!-- Missions & Progress Info -->
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-slate-400 font-mono text-[11px]">
                                    <i class="fas fa-puzzle-piece text-cyan-400 mr-1"></i>
                                    {{ $completedMissions }}/{{ $missionsCount }} {{ Str::plural('Mission', $missionsCount) }}
                                </span>
                                <span class="font-mono font-semibold {{ $currentCompleted ? 'text-emerald-400' : 'text-slate-300' }}">
                                    {{ round($progressPercentage) }}%
                                </span>
                            </div>

                            <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden mb-4">
                                <div class="h-full rounded-full transition-all duration-500 {{ $currentCompleted ? 'bg-emerald-500' : 'bg-cyan-500' }}"
                                     style="width: {{ $progressPercentage }}%"></div>
                            </div>

                            <!-- Action Button or Lock Notice -->
                            @if ($isUnlocked)
                                <a href="{{ route('student.topic', $topic->topic_id) }}" 
                                   class="btn-cyber {{ $currentCompleted ? 'btn-cyber-secondary' : 'btn-cyber-primary' }} text-xs w-full text-center flex items-center justify-center gap-2">
                                    <i class="fas {{ $currentCompleted ? 'fa-eye' : 'fa-play' }}"></i>
                                    <span>{{ $currentCompleted ? 'Review Stage Content' : 'Enter Stage Lab' }}</span>
                                </a>
                            @else
                                <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 text-center">
                                    <p class="text-[11px] text-slate-400 font-mono flex items-center justify-center gap-1.5">
                                        <i class="fas fa-lock text-slate-500"></i>
                                        Prerequisite: Clear Stage {{ $topicNumber - 1 }} first
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @php
                    $previousCompleted = $currentCompleted;
                    $topicNumber++;
                @endphp
            @endforeach
        @else
            <div class="col-span-full py-16 text-center glass-card">
                <i class="fas fa-route text-4xl text-slate-600 mb-3"></i>
                <h3 class="text-base font-bold text-white mb-1">No Learning Topics Published</h3>
                <p class="text-slate-400 text-xs max-w-md mx-auto">
                    Your instructor has not published networking curriculum modules yet. Please check back shortly.
                </p>
            </div>
        @endif
    </div>
</div>

<script>
function filterTopics(status, btn) {
    document.querySelectorAll('.topic-filter-pill').forEach(el => {
        el.classList.remove('active', 'bg-cyan-500/20', 'text-cyan-300', 'border', 'border-cyan-500/30');
        el.classList.add('text-slate-400');
    });
    btn.classList.add('active', 'bg-cyan-500/20', 'text-cyan-300', 'border', 'border-cyan-500/30');
    btn.classList.remove('text-slate-400');

    const cards = document.querySelectorAll('.topic-card');
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
