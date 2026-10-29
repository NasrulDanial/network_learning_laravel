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
<div class="dash-split" style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start;">

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
                <a href="{{ route('lecturer.student-performance.index', ['search' => $student->email]) }}" style="font-size: 0.75rem; color: var(--nl-cyan);">View all</a>
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
                                Topic: {{ $wa->quiz->topic->title ?? 'Curriculum Stage' }} &bull; Taken: {{ $wa->date_taken ? \Illuminate\Support\Carbon::parse($wa->date_taken)->format('d M Y') : '—' }}
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
                @php
                    $badge = fn ($ea) => '<span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24; font-size: 0.76rem; font-weight: 700;"><i class="fa-solid ' . e($ea->achievement->badge_icon ?? 'fa-award') . '" aria-hidden="true"></i> ' . e($ea->achievement->badge_name ?? 'Badge') . '</span>';
                @endphp
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach ($earnedAchievements->take(8) as $ea)
                        {!! $badge($ea) !!}
                    @endforeach
                </div>
                @if ($earnedAchievements->count() > 8)
                    <details class="badge-more">
                        <summary>Show all ({{ $earnedAchievements->count() }})</summary>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                            @foreach ($earnedAchievements->slice(8) as $ea)
                                {!! $badge($ea) !!}
                            @endforeach
                        </div>
                    </details>
                @endif
            @else
                <p style="font-size: 0.8rem; color: var(--nl-text-tertiary); margin: 0;">No honors unlocked yet.</p>
            @endif
        </div>
    </div>

    <!-- Right Column: Full Attempts History -->
    <div style="display: flex; flex-direction: column; gap: 24px; min-width: 0;">
        @php
            $endedLabels = ['finished' => 'Finished', 'link_down' => 'Link down', 'abandoned' => 'Abandoned'];
            $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d) : null;
        @endphp

        <!-- Quiz Attempts Table -->
        <section class="log-panel" aria-labelledby="quizLogTitle">
            <div class="log-panel-head">
                <h3 id="quizLogTitle">
                    Quiz evaluation history
                    <span class="log-panel-total">{{ $quizAttempts->total() }} {{ Str::plural('attempt', $quizAttempts->total()) }}</span>
                </h3>
                <form method="GET" class="log-filters">
                    @foreach (request()->except(['quiz_status', 'quiz_page']) as $key => $value)
                        @if (is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label class="visually-hidden" for="quizStatusFilter">Quiz result</label>
                    <select id="quizStatusFilter" name="quiz_status">
                        <option value="">All results</option>
                        <option value="Pass" @selected($quizStatus === 'Pass')>Passed</option>
                        <option value="Fail" @selected($quizStatus === 'Fail')>Failed</option>
                    </select>
                    <button type="submit" class="button secondary">Apply</button>
                </form>
            </div>

            @if ($quizAttempts->count() > 0)
                <div class="log-panel-body">
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
                                @php $taken = $fmt($qa->date_taken); @endphp
                                <tr>
                                    <td>
                                        <div class="log-title">{{ $qa->quiz->title ?? 'Assessment' }}</div>
                                        <div class="log-sub">{{ $qa->quiz->topic->title ?? '' }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ (int) $qa->score }}/{{ (int) $qa->total_questions }}</strong>
                                        <span class="log-sub">({{ round($qa->percentage) }}%)</span>
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $qa->status === 'Pass' ? 'pass' : 'fail' }}">{{ $qa->status }}</span>
                                        @if ($qa->ended_reason && $qa->ended_reason !== 'finished')
                                            <span class="log-pill log-pill--{{ $qa->ended_reason }}">{{ $endedLabels[$qa->ended_reason] ?? $qa->ended_reason }}</span>
                                        @endif
                                    </td>
                                    <td><span class="log-xp">+{{ (int) $qa->points_earned }} XP</span></td>
                                    <td class="log-date" @if ($taken) title="{{ $taken->diffForHumans() }}" @endif>
                                        {{ $taken ? $taken->format('d M Y, g:i a') : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($quizAttempts->hasPages())
                    <div class="log-pagination">{{ $quizAttempts->links() }}</div>
                @endif
            @else
                <p class="log-empty">{{ $quizStatus ? 'No quiz attempts match this filter.' : 'No quiz evaluations recorded.' }}</p>
            @endif
        </section>

        <!-- Puzzle Attempt Logs: one row per puzzle -->
        <section class="log-panel" aria-labelledby="puzzleLogTitle">
            <div class="log-panel-head">
                <h3 id="puzzleLogTitle">
                    Interactive puzzle attempts
                    <span class="log-panel-total">{{ $puzzleTotals['attempts'] }} {{ Str::plural('attempt', $puzzleTotals['attempts']) }} across {{ $puzzleTotals['puzzles'] }} {{ Str::plural('puzzle', $puzzleTotals['puzzles']) }}</span>
                </h3>
                <form method="GET" class="log-filters">
                    @foreach (request()->except(['mission_id', 'puzzle_status', 'puzzle_page']) as $key => $value)
                        @if (is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label class="visually-hidden" for="missionFilter">Mission</label>
                    <select id="missionFilter" name="mission_id">
                        <option value="">All missions</option>
                        @foreach ($studentMissions as $m)
                            <option value="{{ $m->mission_id }}" @selected((int) $missionId === (int) $m->mission_id)>{{ $m->title }}</option>
                        @endforeach
                    </select>
                    <label class="visually-hidden" for="puzzleStatusFilter">Status</label>
                    <select id="puzzleStatusFilter" name="puzzle_status">
                        <option value="">All</option>
                        <option value="solved" @selected($puzzleStatus === 'solved')>Solved</option>
                        <option value="unsolved" @selected($puzzleStatus === 'unsolved')>Unsolved</option>
                    </select>
                    <button type="submit" class="button secondary">Apply</button>
                </form>
            </div>

            @if ($puzzleSummary->count() > 0)
                <div class="log-panel-body">
                    <table class="table-borderless-glass log-puzzles">
                        <thead>
                            <tr>
                                <th>Puzzle / Mission</th>
                                <th>Attempts</th>
                                <th title="XP earned from this puzzle, out of what it is worth">XP</th>
                                <th>Solved</th>
                                <th title="Clean-solve rating of this puzzle: 3 = first try with no hint. The mission rating comes from mission health.">Clean solve</th>
                                <th>Hints</th>
                                <th>Last attempt</th>
                                <th><span class="visually-hidden">Attempts list</span></th>
                            </tr>
                        </thead>
                        @foreach ($puzzleSummary as $pp)
                            @php
                                $attempts = $attemptsByPuzzle->get($pp->puzzle_id, collect());
                                $struggling = ! $pp->is_completed && $pp->attempts_count >= 3;
                                $last = $fmt($pp->last_attempt_at);
                                $rowId = 'puzzle-attempts-' . $pp->puzzle_id;
                            @endphp
                            <tbody class="log-group {{ $struggling ? 'is-struggling' : '' }}">
                                <tr>
                                    <td>
                                        <div class="log-title">{{ $pp->puzzle->question_text ?? 'Interactive Simulation' }}</div>
                                        <div class="log-sub log-sub--cyan">{{ $pp->puzzle->mission->title ?? 'Standalone activity' }}</div>
                                        @if ($struggling)<span class="log-pill log-pill--struggling">Struggling</span>@endif
                                    </td>
                                    <td>{{ (int) $pp->attempts_count }}</td>
                                    <td>{{ (int) $pp->xp_awarded }}/{{ (int) ($pp->puzzle->points ?? 100) }}</td>
                                    <td>
                                        <span class="status-pill {{ $pp->is_completed ? 'pass' : 'fail' }}">{{ $pp->is_completed ? 'Solved' : 'Not yet' }}</span>
                                    </td>
                                    <td aria-label="{{ (int) $pp->best_stars }} of 3 stars">
                                        <span class="log-stars" aria-hidden="true">@for ($s = 1; $s <= 3; $s++)<i class="fa-solid fa-star {{ $s <= $pp->best_stars ? 'is-lit' : '' }}"></i>@endfor</span>
                                    </td>
                                    <td>{{ (int) $pp->hints_used > 0 ? 'Bought' : '—' }}</td>
                                    <td class="log-date" @if ($last) title="{{ $last->diffForHumans() }}" @endif>{{ $last ? $last->format('d M Y, g:i a') : '—' }}</td>
                                    <td>
                                        <button type="button" class="button secondary log-toggle" aria-expanded="false" aria-controls="{{ $rowId }}"
                                                onclick="const open = this.getAttribute('aria-expanded') !== 'true'; this.setAttribute('aria-expanded', open); document.getElementById('{{ $rowId }}').hidden = !open; this.querySelector('span').textContent = open ? 'Hide' : 'View';">
                                            <span>View</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr id="{{ $rowId }}" class="log-attempts" hidden>
                                    <td colspan="8">
                                        <ol class="log-attempt-list">
                                            @foreach ($attempts as $a)
                                                @php $when = $fmt($a->date_taken); @endphp
                                                <li>
                                                    <span class="log-attempt-no">#{{ $a->attempt_no }}</span>
                                                    <span>{{ (int) $a->score }} pts</span>
                                                    <span class="status-pill {{ $a->status === 'completed' ? 'pass' : 'fail' }}">{{ $a->status === 'completed' ? 'Correct' : 'Wrong' }}</span>
                                                    <span class="log-date" @if ($when) title="{{ $when->diffForHumans() }}" @endif>{{ $when ? $when->format('d M Y, g:i a') : '—' }}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                        @if ($pp->attempts_count > $attempts->count())
                                            <p class="log-sub">Showing the latest {{ $attempts->count() }} of {{ (int) $pp->attempts_count }} attempts.</p>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
                @if ($puzzleSummary->hasPages())
                    <div class="log-pagination">{{ $puzzleSummary->links() }}</div>
                @endif
            @else
                <p class="log-empty">{{ $missionId || $puzzleStatus ? 'No puzzles match these filters.' : 'No interactive puzzle attempts recorded.' }}</p>
            @endif
        </section>

    </div>
</div>
@endsection
