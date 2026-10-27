@extends('layouts.app')

@section('content')
<!-- Top Back Breadcrumb -->
<div style="margin-bottom: 20px;">
    <a class="breadcrumb-link" href="{{ route('lecturer.student-performance.index') }}">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Return to Student Performance Telemetry</span>
    </a>
</div>

<!-- Student Header Dossier Card -->
<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 26px; margin-bottom: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: rgba(34, 211, 238, 0.15); border: 1px solid rgba(34, 211, 238, 0.35); color: var(--nl-cyan); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                <i class="fa-solid {{ $rankInfo['current_rank_icon'] ?? 'fa-user-astronaut' }}"></i>
            </div>
            <div>
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 4px;">Student Operative Dossier</span>
                <h1 style="font-size: 1.6rem; font-weight: 800; margin: 0; color: #FFFFFF;">{{ $student->name }}</h1>
                <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px; font-size: 0.82rem; color: var(--nl-text-secondary); flex-wrap: wrap;">
                    <span><i class="fa-solid fa-envelope" style="margin-right: 4px;"></i> {{ $student->email }}</span>
                    @if ($student->student_id)
                        <span>&bull;</span>
                        <span><i class="fa-solid fa-id-badge" style="margin-right: 4px;"></i> ID: {{ $student->student_id }}</span>
                    @endif
                    @if ($student->programme)
                        <span>&bull;</span>
                        <span><i class="fa-solid fa-graduation-cap" style="margin-right: 4px;"></i> {{ $student->programme }}</span>
                    @endif
                    @if ($student->semester)
                        <span>(Semester {{ $student->semester }})</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Telemetry Stats -->
        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--nl-text-tertiary); display: block; font-weight: 600;">Accumulated XP</span>
                <strong style="font-size: 1.5rem; font-weight: 800; color: #fbbf24;">{{ number_format($totalXp) }} <span style="font-size: 0.85rem;">XP</span></strong>
            </div>
            <div style="width: 1px; height: 36px; background: var(--nl-glass-border);"></div>
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--nl-text-tertiary); display: block; font-weight: 600;">Clearance Level</span>
                <strong style="font-size: 1.5rem; font-weight: 800; color: var(--nl-cyan);">Tier {{ $rankInfo['current_level'] }}</strong>
            </div>
        </div>
    </div>

    <!-- Career Rank Bar -->
    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--nl-glass-border);">
        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--nl-text-secondary); margin-bottom: 6px;">
            <span><strong>Rank:</strong> {{ $rankInfo['current_rank_title'] }}</span>
            <span>{{ $rankInfo['tier_progress_xp'] }} / 500 XP to next tier</span>
        </div>
        <div style="height: 6px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; overflow: hidden;">
            <div style="width: {{ $rankInfo['tier_progress_pct'] }}%; height: 100%; background: var(--nl-gradient-accent); border-radius: 99px;"></div>
        </div>
    </div>
</div>

<!-- Split Grid: Topic Completion & Bottlenecks (Left) + Attempts History (Right) -->
<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start;">

    <!-- Left Column: Topic Completion & Weak Areas -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Topic Mastery Timeline -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                    Curriculum Progression
                </h3>
                <span style="font-size: 0.75rem; color: var(--nl-text-tertiary);">Stage-by-stage mastery</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($topicProgress as $tp)
                    @php
                        $isComp = $tp->learning_status === 'Completed';
                        $isProg = $tp->learning_status === 'In Progress';
                    @endphp
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 12px; padding: 12px 14px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-weight: 700; font-size: 0.86rem; color: #FFFFFF;">
                                Stage {{ $tp->topic_order }}: {{ $tp->title }}
                            </span>
                            <span class="status-pill {{ $isComp ? 'pass' : ($isProg ? 'ready' : 'fail') }}" style="font-size: 0.68rem;">
                                {{ $tp->learning_status ?? 'Not Started' }}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.74rem; color: var(--nl-text-secondary); margin-bottom: 4px;">
                            <span>Completion: {{ round($tp->progress_percentage ?? 0) }}%</span>
                            <span>Quiz: {{ $tp->quiz_status ?? 'Not Attempted' }}</span>
                        </div>
                        <div style="height: 4px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden;">
                            <div style="width: {{ $tp->progress_percentage ?? 0 }}%; height: 100%; background: {{ $isComp ? '#22c55e' : 'var(--nl-cyan)' }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Learning Bottlenecks / Weak Areas (Blueprint B5) -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #f87171;"></i>
                    <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                        Identified Learning Difficulties
                    </h3>
                </div>
                <span style="font-size: 0.72rem; color: var(--nl-text-tertiary);">Score &lt; 70%</span>
            </div>

            @if ($weakAreas->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach ($weakAreas as $wa)
                        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; padding: 12px 14px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <strong style="font-size: 0.85rem; color: #FFFFFF;">
                                    {{ $wa->quiz->title ?? 'Assessment' }}
                                </strong>
                                <span class="status-pill fail" style="font-size: 0.68rem;">{{ round($wa->percentage) }}%</span>
                            </div>
                            <div style="font-size: 0.74rem; color: var(--nl-text-secondary);">
                                Topic: {{ $wa->quiz->topic->title ?? 'Curriculum Stage' }} &bull; Taken: {{ $wa->date_taken }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 24px 12px; color: var(--nl-text-tertiary); font-size: 0.82rem;">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.8rem; color: #4ade80; margin-bottom: 8px; display: block;"></i>
                    No recurring bottlenecks or failed challenges recorded for this operative.
                </div>
            @endif
        </div>

        <!-- Earned Honors -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 14px 0; color: #FFFFFF;">
                Clearance Honors ({{ $earnedAchievements->count() }})
            </h3>
            @if ($earnedAchievements->count() > 0)
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach ($earnedAchievements as $ea)
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24; font-size: 0.76rem; font-weight: 700;">
                            <i class="fa-solid {{ $ea->achievement->badge_icon ?? 'fa-award' }}"></i>
                            {{ $ea->achievement->badge_name }}
                        </span>
                    @endforeach
                </div>
            @else
                <p style="font-size: 0.8rem; color: var(--nl-text-tertiary); margin: 0;">No honors unlocked yet.</p>
            @endif
        </div>
    </div>

    <!-- Right Column: Full Attempts History -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Quiz Attempts Table -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                    Quiz Evaluation History ({{ $quizAttempts->count() }})
                </h3>
            </div>

            @if ($quizAttempts->count() > 0)
                <table class="table-borderless-glass">
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>XP</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizAttempts as $qa)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #FFFFFF; font-size: 0.86rem;">
                                        {{ $qa->quiz->title ?? 'Assessment' }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--nl-text-tertiary);">
                                        {{ $qa->quiz->topic->title ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: #FFFFFF;">{{ (int) $qa->score }}/{{ (int) $qa->total_questions }}</strong>
                                    <span style="font-size: 0.72rem; color: var(--nl-text-secondary);">({{ round($qa->percentage) }}%)</span>
                                </td>
                                <td>
                                    <span class="status-pill {{ $qa->status === 'Pass' ? 'pass' : 'fail' }}">
                                        {{ $qa->status }}
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #fbbf24; font-weight: 700; font-size: 0.8rem;">
                                        +{{ (int) $qa->points_earned }} XP
                                    </span>
                                </td>
                                <td style="font-size: 0.75rem; color: var(--nl-text-secondary);">
                                    {{ $qa->date_taken }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size: 0.85rem; color: var(--nl-text-tertiary); text-align: center; padding: 24px 0; margin: 0;">No quiz evaluations recorded.</p>
            @endif
        </div>

        <!-- Puzzle Attempt Logs -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                    Interactive Puzzle Attempts ({{ $puzzleAttempts->count() }})
                </h3>
            </div>

            @if ($puzzleAttempts->count() > 0)
                <table class="table-borderless-glass">
                    <thead>
                        <tr>
                            <th>Puzzle Activity</th>
                            <th>Score</th>
                            <th>Outcome</th>
                            <th>Attempt #</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($puzzleAttempts as $pa)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #FFFFFF; font-size: 0.84rem;">
                                        {{ $pa->puzzle->question_text ?? 'Interactive Simulation' }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--nl-cyan);">
                                        {{ $pa->puzzle->mission->title ?? 'PBL Mission' }}
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: #FFFFFF;">{{ (int) $pa->score }} pts</strong>
                                </td>
                                <td>
                                    <span class="status-pill {{ $pa->status === 'completed' ? 'pass' : 'fail' }}">
                                        {{ ucfirst($pa->status) }}
                                    </span>
                                </td>
                                <td style="font-size: 0.78rem; color: var(--nl-text-secondary);">
                                    #{{ $pa->attempt_no }}
                                </td>
                                <td style="font-size: 0.75rem; color: var(--nl-text-secondary);">
                                    {{ $pa->date_taken }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size: 0.85rem; color: var(--nl-text-tertiary); text-align: center; padding: 24px 0; margin: 0;">No interactive puzzle attempts recorded.</p>
            @endif
        </div>

    </div>
</div>
@endsection
