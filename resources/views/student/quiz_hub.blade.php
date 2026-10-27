@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="glass-card p-6 lg:p-8 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge-cyber badge-cyber-primary">
                        <i class="fas fa-bullseye mr-1.5"></i> Assessment & Drills
                    </span>
                    <span class="text-xs text-slate-400 font-mono">PRACTICE ARENA</span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-white mb-2">
                    Theoretical Knowledge Challenges
                </h1>
                <p class="text-slate-400 max-w-2xl text-sm leading-relaxed">
                    Test your networking theory comprehension through stage-specific question evaluations. Passing quizzes reinforces your foundational concepts and awards XP towards your clearance tier.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('student.learning') }}" class="btn-cyber btn-cyber-primary text-xs">
                    <i class="fas fa-rocket mr-1.5"></i> Curriculum Path
                </a>
                <a href="{{ route('student.progress') }}" class="btn-cyber btn-cyber-secondary text-xs">
                    <i class="fas fa-chart-line mr-1.5"></i> Performance
                </a>
            </div>
        </div>
    </div>

    <!-- Practice Arena Disclaimer Note -->
    <div class="p-4 rounded-xl bg-slate-900/60 border border-cyan-500/30 flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-sm shrink-0 mt-0.5">
            <i class="fas fa-info"></i>
        </div>
        <div class="text-xs text-slate-300 leading-relaxed">
            <strong class="text-white font-semibold">Self-Paced Practice:</strong>
            These question assessments validate your protocol and conceptual understanding. You can repeat assessments anytime to solidify your networking knowledge. For hands-on interactive labs, visit the <a href="{{ route('student.learning') }}" class="text-cyan-400 underline hover:text-cyan-300">Curriculum Journey</a>.
        </div>
    </div>

    <!-- Errors / Alerts -->
    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Notice</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Quiz Challenges Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @if ($quizzes->count() > 0)
            @php
                $previousCompleted = true;
                $topicNumber = 1;
            @endphp

            @foreach ($quizzes as $quizItem)
                @php
                    $isFirst = (int) $quizItem->topic_order === 1;
                    $isUnlocked = $isFirst || $previousCompleted;
                    $learningStatus = $quizItem->learning_status ?? 'Not Started';
                    $quizStatus = $quizItem->quiz_status ?? 'Not Attempted';
                    $isPassed = $quizStatus === 'Passed';
                    $isFailed = $quizStatus === 'Failed';
                @endphp

                <div class="glass-card p-6 flex flex-col justify-between transition-all duration-300 {{ $isPassed ? 'border-emerald-500/30' : ($isUnlocked ? 'border-cyan-500/30' : 'opacity-70') }}">
                    <div>
                        <!-- Header / Stage Meta & Status -->
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl border
                                {{ $isPassed ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : ($isUnlocked ? 'bg-cyan-500/10 border-cyan-500/30 text-cyan-400' : 'bg-slate-800/60 border-slate-700/60 text-slate-500') }}">
                                <i class="fas {{ $isPassed ? 'fa-trophy' : ($isUnlocked ? 'fa-bullseye' : 'fa-lock') }}"></i>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <span class="badge-cyber badge-cyber-primary text-[10px] font-mono">
                                    Stage {{ (int) $quizItem->topic_order }}
                                </span>
                                @if (!$isUnlocked)
                                    <span class="badge-cyber badge-cyber-muted text-[10px]">
                                        <i class="fas fa-lock mr-1"></i> Locked
                                    </span>
                                @elseif ($isPassed)
                                    <span class="badge-cyber badge-cyber-success text-[10px]">
                                        <i class="fas fa-check-circle mr-1"></i> Passed
                                    </span>
                                @elseif ($isFailed)
                                    <span class="badge-cyber badge-cyber-danger text-[10px]">
                                        <i class="fas fa-times-circle mr-1"></i> Retake Needed
                                    </span>
                                @else
                                    <span class="badge-cyber badge-cyber-warning text-[10px]">
                                        <i class="fas fa-clock mr-1"></i> Not Attempted
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Quiz Title & Topic -->
                        <div class="mb-3">
                            <span class="text-xs font-mono text-cyan-400 block mb-1">
                                {{ $quizItem->topic_title }}
                            </span>
                            <h3 class="text-base font-bold text-white tracking-tight">
                                {{ $quizItem->quiz_title }}
                            </h3>
                        </div>

                        <p class="text-xs text-slate-400 mb-4 line-clamp-2 leading-relaxed">
                            {{ $quizItem->topic_description }}
                        </p>
                    </div>

                    <!-- Challenge Metrics & Action -->
                    <div class="pt-4 border-t border-slate-800/80">
                        <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-slate-900/60 border border-slate-800 mb-4">
                            <div>
                                <span class="text-[10px] font-mono uppercase text-slate-500 block">Questions</span>
                                <span class="text-sm font-bold text-white font-mono">
                                    {{ (int) $quizItem->total_questions }} MCQs
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] font-mono uppercase text-slate-500 block">Pass Criteria</span>
                                <span class="text-sm font-bold text-emerald-400 font-mono">
                                    {{ (int) $quizItem->passing_mark }}%
                                </span>
                            </div>
                        </div>

                        @if ($isUnlocked)
                            <a href="{{ route('student.quiz.take', $quizItem->quiz_id) }}" 
                               class="btn-cyber {{ $isPassed ? 'btn-cyber-secondary' : 'btn-cyber-primary' }} text-xs w-full text-center flex items-center justify-center gap-2">
                                <i class="fas {{ $isPassed ? 'fa-redo' : 'fa-play' }}"></i>
                                <span>{{ $isPassed ? 'Retake Challenge' : ($isFailed ? 'Retry Challenge' : 'Start Challenge') }}</span>
                            </a>
                        @else
                            <div class="p-2.5 rounded-xl bg-slate-900/40 border border-slate-800 text-center">
                                <p class="text-[11px] text-slate-500 font-mono flex items-center justify-center gap-1.5">
                                    <i class="fas fa-lock"></i>
                                    Prerequisite: Complete Stage {{ $quizItem->topic_order - 1 }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                @php
                    $previousCompleted = $learningStatus === 'Completed';
                @endphp
            @endforeach
        @else
            <div class="col-span-full py-16 text-center glass-card">
                <i class="fas fa-clipboard-list text-4xl text-slate-600 mb-3"></i>
                <h3 class="text-base font-bold text-white mb-1">No Practice Quizzes Available</h3>
                <p class="text-slate-400 text-xs max-w-md mx-auto">
                    Your lecturer has not configured any quiz evaluations yet.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
