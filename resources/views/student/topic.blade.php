@extends('layouts.app')

@section('content')
    @php
        $runMode = (bool) config('netlearn.run_mode');
        $missionsDone = $missionsCleared['done'] === $missionsCleared['total'];
        $assessmentLocked = $quiz && ! $assessmentOpen;
        $objectives = collect(preg_split('/\r\n|\r|\n/', (string) $topic->objectives))
            ->map(fn ($line) => trim(ltrim(trim($line), "-•* ")))
            ->filter();
        $nextMission = $missions->first(fn ($m) => $m->student_is_unlocked && ! $m->student_is_completed && $m->puzzles->count() > 0);
    @endphp

    @if (session('error'))
        <div class="message error" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> {{ session('error') }}</div>
    @endif
    <a class="breadcrumb-link" href="{{ route('student.learning') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Learning Journey
    </a>

    <!-- Topic Header Banner -->
    <div class="topic-banner">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
            <div style="flex: 1; min-width: 240px;">
                <span class="topic-banner-eyebrow">Stage {{ $topic->topic_order }}  &bull;  Curriculum Zone</span>
                <h1 class="topic-banner-title">{{ $topic->title }}</h1>
                @if ($topic->description)
                    <p class="topic-banner-text">{{ $topic->description }}</p>
                @endif
            </div>

            <!-- Mission Count Badge -->
            <div class="topic-banner-count">
                <div class="topic-banner-count-num">{{ $missionsCleared['done'] }}/{{ $missions->count() }}</div>
                <div class="topic-banner-count-label">{{ Str::plural('Mission', $missions->count()) }} cleared</div>
            </div>
        </div>
    </div>

    <div class="topic-tabs" data-topic-tabs data-topic-id="{{ $topic->topic_id }}">
        {{-- The tab bar appears only with JavaScript; without it every panel is shown in order --}}
        <div class="topic-tablist" role="tablist" aria-label="Topic sections" hidden>
            <button type="button" role="tab" id="tab-btn-overview" aria-controls="tab-overview" aria-selected="true" data-tab="overview">
                <i class="fa-solid fa-compass" aria-hidden="true"></i> Overview
            </button>
            <button type="button" role="tab" id="tab-btn-notes" aria-controls="tab-notes" aria-selected="false" tabindex="-1" data-tab="notes">
                <i class="fa-solid fa-book-open" aria-hidden="true"></i> Study Notes
            </button>
            <button type="button" role="tab" id="tab-btn-missions" aria-controls="tab-missions" aria-selected="false" tabindex="-1" data-tab="missions">
                <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i> Missions
            </button>
            @if ($quiz)
                <button type="button" role="tab" id="tab-btn-assessment" aria-controls="tab-assessment" aria-selected="false" tabindex="-1" data-tab="assessment">
                    <i class="fa-solid {{ $assessmentLocked ? 'fa-lock' : 'fa-shield-halved' }}" aria-hidden="true"></i> Assessment
                    @if ($assessmentLocked)<span class="visually-hidden">(locked)</span>@endif
                </button>
            @endif
        </div>

        <!-- ──────────────── Overview ──────────────── -->
        <section class="topic-panel" role="tabpanel" id="tab-overview" aria-labelledby="tab-btn-overview" tabindex="0">
            <h2 class="topic-panel-title"><i class="fa-solid fa-compass" aria-hidden="true"></i> Overview</h2>

            <div class="overview-grid">
                <div class="overview-card">
                    <h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> What you will learn</h3>
                    @if ($objectives->isNotEmpty())
                        <ul class="overview-objectives">
                            @foreach ($objectives as $objective)
                                <li>{{ $objective }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="overview-muted">Read the study notes, clear the missions, then take the assessment.</p>
                    @endif
                </div>

                <div class="overview-card">
                    <h3><i class="fa-solid fa-list-check" aria-hidden="true"></i> Milestones</h3>
                    <ul class="milestones">
                        <li class="milestone" data-notes-milestone>
                            <i class="fa-regular fa-circle" aria-hidden="true"></i>
                            <span>Study the notes <small data-notes-progress></small></span>
                        </li>
                        <li class="milestone {{ $missionsDone ? 'is-done' : '' }}">
                            <i class="{{ $missionsDone ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle' }}" aria-hidden="true"></i>
                            <span>Clear all missions <small>({{ $missionsCleared['done'] }}/{{ $missionsCleared['total'] }})</small></span>
                            <span class="visually-hidden">{{ $missionsDone ? 'done' : 'not done' }}</span>
                        </li>
                        @if ($quiz)
                            <li class="milestone {{ $quizPassed ? 'is-done' : '' }}">
                                <i class="{{ $quizPassed ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle' }}" aria-hidden="true"></i>
                                <span>Pass the assessment</span>
                                <span class="visually-hidden">{{ $quizPassed ? 'done' : 'not done' }}</span>
                            </li>
                        @endif
                    </ul>

                    <div class="overview-next">
                        @if (! $missionsDone && $nextMission)
                            <a class="button" href="{{ $runMode ? route('student.mission.run', $nextMission->mission_id) : '#mission-' . $nextMission->mission_id }}" data-tab-link="{{ $runMode ? '' : 'missions' }}">
                                <i class="fa-solid fa-play" aria-hidden="true"></i>
                                {{ ! $runMode ? 'Open mission' : ($nextMission->student_done_count > 0 ? 'Continue mission' : 'Start mission') }}: {{ $nextMission->title }}
                            </a>
                            <a class="button secondary" href="#tab-notes" data-tab-link="notes" data-notes-cta>
                                <i class="fa-solid fa-book-open" aria-hidden="true"></i> Read notes
                            </a>
                        @elseif ($quiz && ! $quizPassed)
                            <a class="button" href="{{ route('student.quiz.take', $quiz->quiz_id) }}">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Take the assessment
                            </a>
                        @else
                            <a class="button secondary" href="{{ route('student.learning') }}">
                                <i class="fa-solid fa-route" aria-hidden="true"></i> Back to Learning Journey
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- ──────────────── Study Notes ──────────────── -->
        <section class="topic-panel" role="tabpanel" id="tab-notes" aria-labelledby="tab-btn-notes" tabindex="0">
            <h2 class="topic-panel-title"><i class="fa-solid fa-book-open" aria-hidden="true"></i> Study Notes</h2>

            @if (count($noteSections))
                <div class="notes-layout">
                    <nav class="notes-toc" aria-label="Notes contents">
                        <p class="notes-toc-title">Contents</p>
                        <ol>
                            @foreach ($noteSections as $section)
                                <li><a href="#{{ $section['id'] }}" data-note-link="{{ $section['id'] }}">{{ $section['title'] }}</a></li>
                            @endforeach
                        </ol>
                    </nav>

                    <div class="notes-body">
                        <div class="notes-toolbar">
                            <label class="visually-hidden" for="notesJump">Jump to section</label>
                            <select id="notesJump" class="notes-jump">
                                @foreach ($noteSections as $section)
                                    <option value="{{ $section['id'] }}">{{ $section['title'] }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="button secondary notes-toggle-all" data-notes-toggle aria-pressed="false">
                                <i class="fa-solid fa-up-right-and-down-left-from-center" aria-hidden="true"></i> <span>Expand all</span>
                            </button>
                        </div>

                        @foreach ($noteSections as $section)
                            <details class="note-section" id="{{ $section['id'] }}" {{ $loop->first ? 'open' : '' }}>
                                <summary>{{ $section['title'] }}</summary>
                                <div class="note-section-body">{!! nl2br(e($section['body'])) !!}</div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="overview-muted">Your lecturer has not added study notes for this topic yet.</p>
            @endif
        </section>

        <!-- ──────────────── Missions ──────────────── -->
        <section class="topic-panel" role="tabpanel" id="tab-missions" aria-labelledby="tab-btn-missions" tabindex="0">
            <h2 class="topic-panel-title"><i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i> Learning Missions & Puzzle Challenges</h2>

    @if ($missions->count() > 0)
        @include('student.partials.topology_board')

        <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 32px;">
            @php
                $typeLabels = [
                    'identification' => ['label' => 'Identification', 'icon' => 'fa-eye', 'color' => '#38bdf8'],
                    'matching'       => ['label' => 'Matching', 'icon' => 'fa-arrows-split-up-and-left', 'color' => '#a78bfa'],
                    'sequencing'     => ['label' => 'Sequencing', 'icon' => 'fa-arrow-down-1-9', 'color' => '#facc15'],
                    'classification' => ['label' => 'Classification', 'icon' => 'fa-folder-tree', 'color' => '#34d399'],
                    'drag_and_drop'  => ['label' => 'Drag & Drop', 'icon' => 'fa-hand-pointer', 'color' => '#f472b6'],
                    'scenario'       => ['label' => 'Scenario Decision', 'icon' => 'fa-code-branch', 'color' => '#fb923c'],
                ];
            @endphp

            @foreach ($missions as $mission)
                @php
                    $isUnlocked = $mission->student_is_unlocked;
                    $isCompleted = $mission->student_is_completed;
                @endphp

                <div id="mission-{{ $mission->mission_id }}" class="mission-card" style="background: rgba(13, 33, 56, 0.65); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid {{ $isCompleted ? 'rgba(34, 197, 94, 0.35)' : ($isUnlocked ? 'rgba(255, 255, 255, 0.12)' : 'rgba(255, 255, 255, 0.06)') }}; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.3); opacity: {{ $isUnlocked ? '1' : '0.6' }};">

                    <!-- Mission Header -->
                    <div style="padding: 22px 24px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.07); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 240px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                                <!-- Mission Index -->
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: {{ $isCompleted ? 'rgba(34, 197, 94, 0.25)' : 'rgba(34, 211, 238, 0.15)' }}; border: 1px solid {{ $isCompleted ? 'rgba(34, 197, 94, 0.5)' : 'rgba(34, 211, 238, 0.35)' }}; font-weight: 800; font-size: 0.9rem; color: {{ $isCompleted ? '#4ade80' : '#22d3ee' }};">
                                    {{ $mission->mission_order }}
                                </span>

                                @if ($isCompleted)
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 6px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35); color: #4ade80; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-check"></i> CLEARED
                                    </span>
                                @elseif (!$isUnlocked)
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 6px; background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.25); color: #94a3b8; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-lock"></i> LOCKED
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 6px; background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.25); color: #22d3ee; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-signal"></i> ACTIVE
                                    </span>
                                @endif

                                <!-- Difficulty -->
                                @php
                                    $diffColor = ['beginner' => '#4ade80', 'intermediate' => '#facc15', 'advanced' => '#f87171'][$mission->difficulty] ?? '#94a3b8';
                                @endphp
                                <span style="font-size: 0.73rem; font-weight: 700; text-transform: uppercase; color: {{ $diffColor }};">
                                    {{ ucfirst($mission->difficulty) }}
                                </span>
                            </div>

                            <h3 style="font-size: 1.1rem; font-weight: 800; color: #FFFFFF; margin: 0 0 4px 0;">{{ $mission->title }}</h3>

                            @if ($mission->objective)
                                <p style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.65); margin: 0; line-height: 1.4; max-width: 550px;">
                                    {{ Str::limit($mission->objective, 140) }}
                                </p>
                            @endif
                        </div>

                        <!-- Meta Badges & XP -->
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 800; color: #c084fc;" title="XP earned from this mission, out of the most it can give (activities plus the mission reward)">
                                <i class="fa-solid fa-bolt"></i>
                                {{ $mission->student_xp_earned }} / {{ $mission->student_xp_possible }} XP
                            </div>

                            <div class="mission-solved-count">
                                {{ $mission->student_done_count }} of {{ $mission->puzzles->count() }} {{ Str::plural('task', $mission->puzzles->count()) }} solved
                            </div>

                            @if ($isCompleted)
                                <div class="mission-stars" aria-label="Best rating: {{ $mission->student_stars }} of 3 stars" title="Best rating: the health segments you had left when you finished the mission">
                                    @for ($s = 1; $s <= 3; $s++)
                                        <i class="fa-solid fa-star {{ $s <= $mission->student_stars ? 'is-lit' : '' }}" aria-hidden="true"></i>
                                    @endfor
                                </div>
                            @endif

                            @if ($mission->student_run_active)
                                @php $runStars = \App\Services\LinkHealthService::stars($mission->student_run_health); @endphp
                                <div class="mission-run-health" title="Mission health of the run in progress: each third of the bar is a star">
                                    <span class="mission-run-health-label">Run health {{ $mission->student_run_health }}/100 · {{ str_repeat('★', $runStars) }}</span>
                                    <span class="mission-run-health-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $mission->student_run_health }}"><span style="width: {{ $mission->student_run_health }}%"></span></span>
                                </div>
                            @endif

                            @if ($runMode && $isUnlocked && $mission->puzzles->count() > 0)
                                @php
                                    $runLabel = $isCompleted
                                        ? ($mission->student_stars < 3 ? 'Replay for ★★★' : 'Replay mission')
                                        : ($mission->student_done_count > 0 ? 'Continue mission' : 'Start mission');
                                @endphp
                                <a class="button mission-run-btn" href="{{ route('student.mission.run', $mission->mission_id) }}{{ $isCompleted ? '?restart=1' : '' }}">
                                    <i class="fa-solid {{ $isCompleted ? 'fa-rotate-right' : 'fa-play' }}" aria-hidden="true"></i>
                                    {{ $runLabel }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Puzzle Activities List -->
                    @if ($isUnlocked && $mission->puzzles->count() > 0)
                        <details class="practice-list" {{ $runMode ? '' : 'open' }}>
                            <summary>
                                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                {{ $runMode ? 'Practice single activities' : 'Activities' }}
                                <span>({{ $mission->student_done_count }}/{{ $mission->puzzles->count() }} solved)</span>
                            </summary>
                        <div style="padding: 4px 24px 20px;">
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @foreach ($mission->puzzles as $puzzle)
                                    @php
                                        $pMeta = $typeLabels[$puzzle->puzzle_type] ?? ['label' => ucfirst($puzzle->puzzle_type), 'icon' => 'fa-puzzle-piece', 'color' => '#22d3ee'];
                                        $pCompleted = $puzzle->student_is_completed ?? false;
                                        $pAttempts = $puzzle->student_attempts ?? 0;
                                    @endphp
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 16px; border-radius: 12px; background: rgba(255, 255, 255, 0.03); border: 1px solid {{ $pCompleted ? 'rgba(34, 197, 94, 0.2)' : 'rgba(255, 255, 255, 0.06)' }}; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 240px;">
                                            <!-- Order -->
                                            <span style="font-weight: 800; color: {{ $pCompleted ? '#4ade80' : 'rgba(255, 255, 255, 0.5)' }}; font-size: 0.85rem; min-width: 24px; text-align: center;">
                                                @if ($pCompleted)
                                                    <i class="fa-solid fa-check-circle" style="color: #4ade80;"></i>
                                                @else
                                                    {{ $puzzle->activity_order ?? $loop->iteration }}
                                                @endif
                                            </span>

                                            <div>
                                                <!-- Type Badge -->
                                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 5px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: {{ $pMeta['color'] }}; font-size: 0.72rem; font-weight: 700; margin-bottom: 4px;">
                                                    <i class="fa-solid {{ $pMeta['icon'] }}"></i>
                                                    {{ $pMeta['label'] }}
                                                </span>

                                                <div style="font-weight: 700; font-size: 0.92rem; color: #FFFFFF;">{{ $puzzle->question_text }}</div>

                                                <div class="puzzle-facts">
                                                    <span title="XP earned from this activity, out of what it is worth">
                                                        <i class="fa-solid fa-bolt" style="color: #a855f7; font-size: 0.72rem;"></i>
                                                        {{ $pCompleted ? $puzzle->student_xp . '/' . $puzzle->points . ' XP earned' : $puzzle->points . ' XP available' }}
                                                    </span>
                                                    @if ($pAttempts > 0)
                                                        <span>
                                                            <i class="fa-solid fa-rotate-right" style="font-size: 0.72rem;"></i>
                                                            @if ($pCompleted && $puzzle->student_solved_on)
                                                                Solved on attempt {{ $puzzle->student_solved_on }}@if ($pAttempts > $puzzle->student_solved_on) ({{ $pAttempts }} in total, counting replays)@endif
                                                            @else
                                                                {{ $pAttempts }} {{ Str::plural('attempt', $pAttempts) }} so far
                                                            @endif
                                                        </span>
                                                    @endif
                                                    @if ($puzzle->student_hint)
                                                        <span style="color: #fbbf24;"><i class="fa-solid fa-lightbulb" style="font-size: 0.72rem;"></i> Hint used (−{{ (int) config('netlearn.hint_xp_penalty') }} XP)</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Play Button -->
                                        <a
                                            href="{{ route('puzzles.show', $puzzle->puzzle_id) }}"
                                            style="padding: 9px 18px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 7px; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
                                                   {{ $pCompleted
                                                      ? 'background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35); color: #4ade80;'
                                                      : 'background: linear-gradient(135deg, rgba(34, 211, 238, 0.2), rgba(168, 85, 247, 0.2)); border: 1px solid rgba(34, 211, 238, 0.45); color: #22d3ee; box-shadow: 0 0 12px rgba(34, 211, 238, 0.2);' }}"
                                        >
                                            <i class="fa-solid {{ $pCompleted ? 'fa-rotate-right' : 'fa-play' }}"></i>
                                            {{ $pCompleted ? 'Retry Challenge' : 'Launch Puzzle' }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        </details>
                    @elseif (!$isUnlocked)
                        <div style="padding: 18px 24px; text-align: center; color: rgba(255, 255, 255, 0.4); font-size: 0.88rem;">
                            <i class="fa-solid fa-lock" style="font-size: 1.2rem; margin-bottom: 6px; display: block;"></i>
                            Complete the previous mission to unlock this stage.
                        </div>
                    @elseif ($mission->puzzles->count() === 0)
                        <div style="padding: 18px 24px; text-align: center; color: rgba(255, 255, 255, 0.4); font-size: 0.88rem;">
                            <i class="fa-solid fa-clock" style="font-size: 1.2rem; margin-bottom: 6px; display: block;"></i>
                            Puzzle activities are being authored by your lecturer. Check back soon.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div style="background: rgba(13, 33, 56, 0.5); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 40px 24px; text-align: center; margin-bottom: 28px; color: rgba(255, 255, 255, 0.5);">
            <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.4;"></i>
            <p style="font-size: 1rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">No puzzle missions published for this topic yet</p>
            <p style="font-size: 0.85rem;">Your lecturer is preparing gamified challenges for this curriculum stage.</p>
        </div>
    @endif
        </section>

        <!-- ──────────────── Assessment ──────────────── -->
        @if ($quiz)
            <section class="topic-panel" role="tabpanel" id="tab-assessment" aria-labelledby="tab-btn-assessment" tabindex="0">
                <h2 class="topic-panel-title"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Assessment</h2>

                <div class="assessment-card {{ $assessmentLocked ? 'is-locked' : '' }}">
                    <div class="assessment-head">
                        <div>
                            <h3>{{ $quiz->title }}</h3>
                            <p class="overview-muted">
                                {{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}
                                &bull; Pass mark {{ (int) $quiz->passing_mark }}%
                                &bull; {{ $claimableXp > 0 ? '+' . $claimableXp . ' XP available' : 'XP already claimed' }}
                            </p>
                        </div>
                        @if ($assessmentLocked)
                            <span class="assessment-lock"><i class="fa-solid fa-lock" aria-hidden="true"></i> Locked</span>
                        @endif
                    </div>

                    @if ($assessmentLocked)
                        <p>Clear all missions to unlock ({{ $missionsCleared['done'] }}/{{ $missionsCleared['total'] }}).</p>
                        <div class="hub-mission-bar" role="progressbar" aria-label="Missions cleared"
                             aria-valuemin="0" aria-valuemax="{{ $missionsCleared['total'] }}" aria-valuenow="{{ $missionsCleared['done'] }}">
                            <span style="width: {{ $missionsCleared['total'] ? round($missionsCleared['done'] / $missionsCleared['total'] * 100) : 0 }}%"></span>
                        </div>
                        <a class="button secondary" href="#tab-missions" data-tab-link="missions" style="margin-top: 14px;">
                            <i class="fa-solid fa-route" aria-hidden="true"></i> Go to missions
                        </a>
                    @else
                        <ul class="assessment-rules">
                            <li><i class="fa-solid fa-heart" aria-hidden="true"></i> {{ \App\Models\Quiz::lives() }} lives: the {{ \App\Models\Quiz::lives() === 3 ? '3rd' : 'last' }} wrong answer ends the attempt.</li>
                            <li><i class="fa-solid fa-forward" aria-hidden="true"></i> Answers are checked as you go and cannot be changed.</li>
                            <li><i class="fa-solid fa-door-open" aria-hidden="true"></i> Leaving part-way counts as a failed attempt.</li>
                        </ul>
                        @if ($quizSummary && $quizSummary['attempts'] > 0)
                            <p class="overview-muted">
                                {{ $quizSummary['attempts'] }} {{ Str::plural('attempt', $quizSummary['attempts']) }}
                                &bull; best <strong>{{ (int) round($quizSummary['best']) }}%</strong>
                                ({{ $bestResult && $bestResult->status === 'Pass' ? 'passed' : 'not passed' }})
                                @if ($quizSummary['last'])
                                    &bull; last <strong>{{ (int) round($quizSummary['last']->percentage) }}%</strong>
                                    @if ($quizSummary['last']->ended_reason === 'link_down') (link down) @elseif ($quizSummary['last']->ended_reason === 'abandoned') (left early) @endif
                                @endif
                            </p>
                        @endif
                        <a class="button" href="{{ route('student.quiz.take', $quiz->quiz_id) }}">
                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                            {{ $bestResult ? 'Retake assessment' : 'Start assessment' }}
                        </a>
                    @endif
                </div>
            </section>
        @endif
    </div>

    <section class="actions" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px;">
        <a class="button secondary" href="{{ route('student.learning') }}">
            <i class="fa-solid fa-route" aria-hidden="true"></i>
            Return to Learning Journey
        </a>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/topic-tabs.js') }}"></script>
@endsection
