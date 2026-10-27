@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Page Banner / Header -->
    <div class="glass-card p-6 lg:p-8 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge-cyber badge-cyber-primary">
                        <i class="fas fa-chart-line mr-1.5"></i> Progress Telemetry
                    </span>
                    <span class="text-xs text-slate-400 font-mono">STUDENT DOSSIER</span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-white mb-2">
                    Learning Progress & Analytics
                </h1>
                <p class="text-slate-400 max-w-2xl text-sm leading-relaxed">
                    Comprehensive performance telemetry tracking your topic mastery, mission clearance, assessment precision, and clearance rank growth.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('student.learning') }}" class="btn-cyber btn-cyber-primary text-xs">
                    <i class="fas fa-rocket mr-1.5"></i> Continue Journey
                </a>
                <a href="{{ route('student.achievements') }}" class="btn-cyber btn-cyber-secondary text-xs">
                    <i class="fas fa-medal mr-1.5"></i> Badges
                </a>
            </div>
        </div>
    </div>

    <!-- Bento Metric Cards (5 Metrics) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total XP -->
        <div class="glass-card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
                <span>TOTAL XP</span>
                <i class="fas fa-bolt text-amber-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-amber-400 font-mono tracking-tight">
                    {{ number_format($totalPoints) }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Quiz + Mission XP</p>
            </div>
        </div>

        <!-- Topics Completed -->
        <div class="glass-card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
                <span>TOPICS COMPLETED</span>
                <i class="fas fa-graduation-cap text-cyan-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-white font-mono tracking-tight">
                    {{ $completedTopics }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Full curriculum stages</p>
            </div>
        </div>

        <!-- Missions Cleared -->
        <div class="glass-card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
                <span>MISSIONS CLEARED</span>
                <i class="fas fa-puzzle-piece text-purple-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-purple-400 font-mono tracking-tight">
                    {{ $completedMissions }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Problem-based labs</p>
            </div>
        </div>

        <!-- Average Score -->
        <div class="glass-card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
                <span>AVG SCORE</span>
                <i class="fas fa-bullseye text-emerald-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-emerald-400 font-mono tracking-tight">
                    {{ number_format($averageScore, 1) }}%
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Across {{ $totalAttempts }} attempt(s)</p>
            </div>
        </div>

        <!-- Pass Rate -->
        <div class="glass-card p-4 flex flex-col justify-between col-span-2 md:col-span-1">
            <div class="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
                <span>PASS RATE</span>
                <i class="fas fa-trophy text-blue-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-cyan-400 font-mono tracking-tight">
                    {{ $passRate }}%
                </div>
                <p class="text-[11px] text-slate-500 mt-1">{{ $passedAttempts }} passed / {{ $totalAttempts }} total</p>
            </div>
        </div>
    </div>

    <!-- Career Rank Clearance Banner -->
    <div class="glass-card p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-blue-600/30 border border-cyan-500/40 text-cyan-400 flex items-center justify-center text-3xl font-mono shadow-lg shadow-cyan-500/10">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="badge-cyber badge-cyber-primary text-xs font-mono">
                            TIER {{ $rankInfo['tier'] }}
                        </span>
                        <h3 class="text-xl font-bold text-white tracking-tight">{{ $rankInfo['title'] }}</h3>
                    </div>
                    <p class="text-xs text-slate-400">
                        {{ $rankInfo['tagline'] }}
                    </p>
                </div>
            </div>

            <!-- Progress Meter -->
            <div class="md:w-1/3">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="text-slate-400 font-mono">Tier Progression</span>
                    <span class="font-mono font-bold text-cyan-400">{{ $rankInfo['tierProgressPct'] }}%</span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden mb-2">
                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 transition-all duration-500" 
                         style="width: {{ $rankInfo['tierProgressPct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
                    <span>{{ number_format($rankInfo['currentXp']) }} XP Current</span>
                    @if($rankInfo['nextTierXp'])
                        <span>Target: {{ number_format($rankInfo['nextTierXp']) }} XP</span>
                    @else
                        <span class="text-amber-400">Apex Clearance Reached</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recommended Reviews Section (If any bottlenecks or low scores) -->
    @if(isset($recommendedReviews) && $recommendedReviews->count() > 0)
        <div class="glass-card p-6 border-amber-500/30 bg-amber-500/5">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-amber-500/20">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-amber-400"></i>
                    <h2 class="text-base font-bold text-white tracking-tight">Recommended Concept Reinforcements</h2>
                </div>
                <span class="badge-cyber badge-cyber-warning text-xs">
                    {{ $recommendedReviews->count() }} Areas Needing Attention
                </span>
            </div>

            <p class="text-xs text-slate-300 mb-4">
                The learning telemetry detected quiz scores below 70% or unpassed evaluations. Reviewing these topics will boost your mastery and clearance tier:
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($recommendedReviews as $rec)
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700/60 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-mono text-cyan-400 block mb-1">
                                {{ $rec->topic_title }}
                            </span>
                            <h4 class="text-sm font-bold text-white mb-2">{{ $rec->quiz_title }}</h4>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="badge-cyber badge-cyber-danger text-xs font-mono">
                                    Score: {{ round($rec->percentage) }}%
                                </span>
                                <span class="text-[11px] text-slate-500 font-mono">
                                    {{ \Carbon\Carbon::parse($rec->date_taken)->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('student.quiz.take', $rec->quiz_id) }}" class="btn-cyber btn-cyber-primary text-xs w-full text-center">
                            <i class="fas fa-redo mr-1.5"></i> Retake Challenge
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Learning Stage Curriculum Table -->
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    <i class="fas fa-stream text-cyan-400"></i> Curriculum Stage Breakdown
                </h2>
                <p class="text-xs text-slate-400 mt-1">Stage-by-stage mastery of problem-based learning missions and quizzes.</p>
            </div>
            <span class="text-xs font-mono text-slate-400">
                Overall: <strong class="text-cyan-400 font-bold">{{ round($overallProgress) }}%</strong>
            </span>
        </div>

        <div class="table-responsive">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-mono text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Stage</th>
                        <th class="py-3 px-4">Curriculum Topic</th>
                        <th class="py-3 px-4">Missions</th>
                        <th class="py-3 px-4">Evaluation Status</th>
                        <th class="py-3 px-4">Stage Progress</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-sm">
                    @forelse($topicProgress as $tp)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-cyan-400 text-xs">
                                Stage {{ $tp->topic_order }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-white block">{{ $tp->title }}</span>
                                <span class="text-xs text-slate-400 line-clamp-1">{{ $tp->description }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($tp->missions_count > 0)
                                    <span class="badge-cyber badge-cyber-primary text-xs font-mono">
                                        <i class="fas fa-puzzle-piece mr-1"></i>
                                        {{ $tp->completed_missions ?? 0 }} / {{ $tp->missions_count }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500 font-mono">None</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($tp->quiz_status === 'Passed')
                                    <span class="badge-cyber badge-cyber-success text-xs font-mono">Passed</span>
                                @elseif($tp->quiz_status === 'Failed')
                                    <span class="badge-cyber badge-cyber-danger text-xs font-mono">Failed</span>
                                @else
                                    <span class="badge-cyber badge-cyber-muted text-xs font-mono">Not Attempted</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 min-w-[140px]">
                                <div class="flex items-center justify-between text-xs font-mono mb-1">
                                    <span class="text-slate-400">{{ (int) $tp->progress_percentage }}%</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full {{ $tp->progress_percentage == 100 ? 'bg-emerald-500' : 'bg-cyan-500' }}"
                                         style="width: {{ $tp->progress_percentage }}%"></div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('student.topic', $tp->topic_id) }}" class="btn-cyber btn-cyber-secondary text-xs">
                                    <i class="fas fa-folder-open mr-1"></i> Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                                No curriculum topics available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Assessment History -->
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    <i class="fas fa-history text-amber-400"></i> Recent Assessment History
                </h2>
                <p class="text-xs text-slate-400 mt-1">Review outcomes, questions answered, and XP gained from recent quiz attempts.</p>
            </div>
            <a href="{{ route('student.quiz') }}" class="btn-cyber btn-cyber-secondary text-xs">
                <i class="fas fa-list-check mr-1.5"></i> Practice Arena
            </a>
        </div>

        @if($recentResults->count() > 0)
            <div class="table-responsive">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-mono text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Evaluation</th>
                            <th class="py-3 px-4">Stage</th>
                            <th class="py-3 px-4">Score</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">XP Earned</th>
                            <th class="py-3 px-4">Date Taken</th>
                            <th class="py-3 px-4 text-right">Review</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-sm">
                        @foreach($recentResults as $res)
                            <tr class="hover:bg-slate-800/20 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-white">
                                    {{ $res->quiz_title }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-cyan-400 font-mono">
                                    {{ $res->topic_title }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-white">
                                    {{ $res->score }} / {{ $res->total_questions }} ({{ round($res->percentage) }}%)
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($res->status === 'Pass')
                                        <span class="badge-cyber badge-cyber-success text-xs font-mono">Pass</span>
                                    @else
                                        <span class="badge-cyber badge-cyber-danger text-xs font-mono">Fail</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-amber-400 font-bold">
                                    +{{ $res->points_earned }} XP
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-400 font-mono">
                                    {{ \Carbon\Carbon::parse($res->date_taken)->format('M d, Y H:i') }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('student.result', $res->result_id) }}" class="btn-cyber btn-cyber-secondary text-xs">
                                        <i class="fas fa-eye mr-1"></i> Breakdown
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-12 text-center">
                <i class="fas fa-clipboard-check text-3xl text-slate-600 mb-3"></i>
                <p class="text-sm text-slate-400">No quiz attempts recorded yet.</p>
                <div class="mt-4">
                    <a href="{{ route('student.quiz') }}" class="btn-cyber btn-cyber-primary text-xs">
                        <i class="fas fa-play mr-1.5"></i> Attempt a Quiz
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
