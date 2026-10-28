@extends('layouts.app')

@section('content')
    @php $runMode = (bool) config('netlearn.run_mode'); @endphp
    @if (session('error'))
        <div class="message error" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> {{ session('error') }}</div>
    @endif
    <a class="breadcrumb-link" href="{{ route('student.learning') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Learning Journey
    </a>

    <!-- Topic Header Banner -->
    <div style="background: linear-gradient(135deg, rgba(13, 33, 56, 0.75) 0%, rgba(7, 20, 38, 0.95) 100%); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(34, 211, 238, 0.2); border-radius: 24px; padding: 28px; margin-bottom: 28px; box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(34, 211, 238, 0.05);">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
            <div style="flex: 1; min-width: 260px;">
                <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-cyan, #22d3ee); display: inline-block; margin-bottom: 8px;">
                    Stage {{ $topic->topic_order }}  &bull;  Curriculum Zone
                </span>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: #FFFFFF; margin: 0 0 8px 0; letter-spacing: -0.02em;">{{ $topic->title }}</h1>
                <p style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7); margin: 0; line-height: 1.5;">{{ $topic->description }}</p>
            </div>

            <!-- Mission Count Badge -->
            <div style="text-align: center; background: rgba(34, 211, 238, 0.08); border: 1px solid rgba(34, 211, 238, 0.25); border-radius: 16px; padding: 16px 24px;">
                <div style="font-size: 2rem; font-weight: 800; color: var(--nl-cyan, #22d3ee); line-height: 1;">{{ $missions->count() }}</div>
                <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.6); margin-top: 4px; font-weight: 600;">{{ Str::plural('Mission', $missions->count()) }}</div>
            </div>
        </div>

        @php
            $objectives = collect(preg_split('/\r\n|\r|\n/', (string) $topic->objectives))
                ->map(fn ($line) => trim(ltrim(trim($line), "-•* ")))
                ->filter();
        @endphp
        @if ($objectives->isNotEmpty())
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                <h2 style="font-size: 0.95rem; font-weight: 700; color: #FFFFFF; margin: 0 0 10px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-bullseye" style="color: var(--nl-cyan, #22d3ee);" aria-hidden="true"></i>
                    What you will learn
                </h2>
                <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.9rem; color: rgba(255, 255, 255, 0.8); line-height: 1.7;">
                    @foreach ($objectives as $objective)
                        <li>{{ $objective }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($topic->content)
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.08); font-size: 0.9rem; color: rgba(255, 255, 255, 0.75); line-height: 1.6;">
                {!! nl2br(e($topic->content)) !!}
            </div>
        @endif
    </div>

    <!-- ──────────────── PBL Missions & Puzzle Activities ──────────────── -->
    <h2 style="font-size: 1.2rem; font-weight: 700; color: #FFFFFF; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-puzzle-piece" style="color: var(--nl-cyan, #22d3ee);"></i>
        Learning Missions & Puzzle Challenges
    </h2>

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
                    $scorePct = $mission->student_score_pct ?? 0;
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
                            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 800; color: #c084fc;">
                                <i class="fa-solid fa-bolt"></i>
                                +{{ $mission->xp_reward }} XP
                            </div>

                            <div style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.6);">
                                Pass: <strong style="color: #FFFFFF;">{{ $mission->pass_mark }}%</strong>
                                &bull;
                                {{ $mission->puzzles_count }} {{ Str::plural('puzzle', $mission->puzzles_count) }}
                            </div>

                            @if ($scorePct > 0)
                                <div style="font-size: 0.78rem; color: {{ $isCompleted ? '#4ade80' : '#facc15' }}; font-weight: 700;">
                                    Best: {{ $scorePct }}%
                                </div>
                            @endif

                            @if ($isCompleted)
                                <div class="mission-stars" aria-label="{{ $mission->student_stars }} of 3 stars">
                                    @for ($s = 1; $s <= 3; $s++)
                                        <i class="fa-solid fa-star {{ $s <= $mission->student_stars ? 'is-lit' : '' }}" aria-hidden="true"></i>
                                    @endfor
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

                                                <div style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.5); margin-top: 2px; display: flex; align-items: center; gap: 10px;">
                                                    <span><i class="fa-solid fa-bolt" style="color: #a855f7; font-size: 0.72rem;"></i> {{ $puzzle->points }} pts</span>
                                                    @if ($pAttempts > 0)
                                                        <span><i class="fa-solid fa-rotate-right" style="font-size: 0.72rem;"></i> {{ $pAttempts }} {{ Str::plural('attempt', $pAttempts) }}</span>
                                                    @endif
                                                    @if (($puzzle->student_best_score ?? 0) > 0)
                                                        <span style="color: #facc15;"><i class="fa-solid fa-star" style="font-size: 0.72rem;"></i> Best: {{ $puzzle->student_best_score }}/{{ $puzzle->points }}</span>
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

    <!-- ──────────────── Conventional Quiz Section ──────────────── -->
    @if ($quiz)
        <div style="background: rgba(13, 33, 56, 0.5); backdrop-filter: blur(20px); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 20px; padding: 22px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #a855f7; margin-bottom: 6px;">
                    <i class="fa-solid fa-shield-halved"></i> Assessment Quiz
                </div>
                <h3 style="font-size: 1rem; font-weight: 700; color: #FFFFFF; margin: 0 0 4px 0;">{{ $quiz->title }}</h3>
                <p style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.6); margin: 0;">
                    Conventional assessment &bull; Pass mark: {{ (int) $quiz->passing_mark }}%
                </p>
            </div>

            <a
                class="button"
                href="{{ route('student.quiz.take', $quiz->quiz_id) }}"
                style="padding: 10px 20px; font-size: 0.88rem; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, rgba(168, 85, 247, 0.3), rgba(139, 92, 246, 0.3)); border: 1px solid rgba(168, 85, 247, 0.5); color: #FFFFFF;"
            >
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Take Assessment Quiz
            </a>
        </div>
    @endif

    <section class="actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a class="button secondary" href="{{ route('student.learning') }}">
            <i class="fa-solid fa-route" aria-hidden="true"></i>
            Return to Learning Journey
        </a>
    </section>
@endsection
