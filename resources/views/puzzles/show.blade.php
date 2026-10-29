@extends('layouts.app')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        #feedback-panel[data-state="correct"] {
            background: rgba(6, 78, 59, 0.4);
            color: #6ee7b7;
            border-color: rgba(52, 211, 153, 0.5);
        }
        #feedback-panel[data-state="incorrect"] {
            background: rgba(136, 19, 55, 0.4);
            color: #fda4af;
            border-color: rgba(251, 113, 133, 0.5);
        }

        /* Arcade/Terminal scanline raster effect */
        #phaser-game::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                to bottom,
                transparent 0px,
                transparent 2px,
                rgba(0, 0, 0, 0.15) 2px,
                rgba(0, 0, 0, 0.15) 4px
            );
            pointer-events: none;
            border-radius: inherit;
        }
    </style>
@endsection

@section('content')
<div class="puzzle-page" style="max-width: 960px; margin: 0 auto; padding: 12px 0;">

    @php
        $inRun = $inRun ?? false;
        $attemptsUsed = $attemptsUsed ?? 0;
        $hintFree = !empty($isPreview) || ($hintFree ?? false);
        $hintRevealed = $hintRevealed ?? false;
        $hintPenalty = $hintPenalty ?? (int) config('netlearn.hint_xp_penalty');
        $tryAttempts = $tryAttempts ?? 0;
        $tryHint = $tryHint ?? false;
        $penaltyPaid = $penaltyPaid ?? false;
        $solved = $solved ?? false;
        $worth = max(0, (int) ($puzzle->points ?: 100));
        $typeLabel = strtoupper(str_replace('_', ' ', $puzzle->puzzle_type));
        $freeAfter = (int) config('netlearn.free_hint_after');
    @endphp

    @if ($inRun)
        {{-- Mission Run: the parent HUD shows progress; keep only a compact instruction line --}}
        <div class="run-instruction">
            <strong>{{ $puzzle->question_text }}</strong>
            <span>{{ $puzzle->instruction }}</span>
        </div>
    @elseif (!empty($isPreview))
        <div class="preview-notice" style="background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.4); color: #facc15; border-radius: 12px; padding: 12px 18px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 0.88rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-flask-vial" aria-hidden="true"></i>
                <span class="preview-notice-long"><strong>LECTURER TEST PREVIEW:</strong> Experiencing puzzle gameplay exactly as students will. Testing mode active — Zero XP credited, Zero attempts recorded.</span>
                <span class="preview-notice-short"><strong>Preview:</strong> no XP or attempts recorded.</span>
            </div>
            @if ($puzzle->mission_id)
                <a class="preview-notice-exit" href="{{ route('lecturer.missions.show', $puzzle->mission_id) }}" style="color: #facc15; font-weight: 700; text-decoration: underline; white-space: nowrap;">Exit Preview</a>
            @endif
        </div>
        {{-- Inside the centred preview dialog the title is in the dialog header: keep just the instruction --}}
        @if ($puzzle->instruction)
            <p class="embedded-instruction">{{ $puzzle->instruction }}</p>
        @endif
    @else
        <div style="margin-bottom: 12px;">
            <a href="{{ route('student.topic', $puzzle->topic_id) }}" style="font-size: 0.85rem; color: var(--nl-cyan, #22d3ee); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Mission Board
            </a>
        </div>
    @endif

    @unless ($inRun)
    <!-- Telemetry Header Card -->
    <div class="puzzle-telemetry-header" style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px; margin-bottom: 20px; box-shadow: 0 16px 36px rgba(0,0,0,0.4);">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
            <div style="flex: 1; min-width: 260px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e;"></span>
                    <span class="sheet-badge" style="font-size: 0.72rem; text-transform: uppercase;">
                        NODE #{{ $puzzle->puzzle_id }} // {{ strtoupper(str_replace('_', ' ', $puzzle->puzzle_type)) }} // ORDER {{ $puzzle->activity_order ?? 1 }}
                    </span>
                </div>
                <h1 style="font-size: 1.45rem; font-weight: 800; color: #FFFFFF; margin: 0 0 4px 0; letter-spacing: -0.02em;">
                    {{ $puzzle->question_text }}
                </h1>
                <p style="font-size: 0.88rem; color: var(--nl-text-secondary); margin: 0; line-height: 1.4;">
                    {{ $puzzle->instruction }}
                </p>
            </div>

            <!-- Network XP Counter -->
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.35); padding: 10px 18px; border-radius: var(--nl-radius-btn); box-shadow: var(--nl-glow-xp); user-select: none;">
                <i class="fa-solid fa-bolt" style="color: #fbbf24; font-size: 1.1rem;"></i>
                <span style="font-weight: 700; color: #fde047; font-size: 0.95rem;">+{{ $puzzle->points }} Network XP</span>
            </div>
        </div>
    </div>

    @endunless

    <div class="puzzle-layout">
    <div class="puzzle-main">
    <!-- Network Lab Terminal Wrapper -->
    <div class="lab-terminal" style="position: relative; border-radius: var(--nl-radius-panel); padding: 6px; background: linear-gradient(180deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), inset 0 1px 1px rgba(255, 255, 255, 0.1); margin-bottom: 20px;">
        <!-- Terminal Header Bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; background: rgba(7, 12, 22, 0.85); border-radius: 18px 18px 0 0; border-bottom: 1px solid rgba(255, 255, 255, 0.06); font-size: 0.75rem; color: var(--nl-text-tertiary);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="display: flex; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                </div>
                <span style="color: #22d3ee; font-weight: 600; margin-left: 6px;">eth0 [Interactive Simulation Node]</span>
            </div>
            <span class="lab-status"><i class="lab-status-dot" aria-hidden="true"></i>{{ $typeLabel }} LAB · LIVE</span>
        </div>

        <!-- Phaser Mount Container -->
        <div id="phaser-game" style="width: 100%; min-height: 480px; background: #070d18; border-radius: 0 0 18px 18px; overflow: hidden; position: relative;">
            <div id="game-loader" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; background: #070d18; z-index: 20;">
                <div style="width: 44px; height: 44px; border: 3px solid rgba(255, 255, 255, 0.1); border-top-color: var(--nl-cyan); border-radius: 50%; animation: phaserSpin 0.9s linear infinite;"></div>
                <span style="font-size: 0.78rem; font-weight: 600; color: var(--nl-cyan); letter-spacing: 0.1em;">
                    INITIALIZING VIRTUAL TOPOLOGY LAB...
                </span>
            </div>
        </div>
    </div>

    <!-- Diagnostic Feedback Panel (shown under the board) -->
    <div id="feedback-panel"
         style="display: none; margin-top: 16px; padding: 16px; border-radius: var(--nl-radius-card); border: 2px solid transparent; font-weight: 500; font-size: 0.9rem; line-height: 1.5; backdrop-filter: var(--nl-glass-blur); transition: var(--nl-transition);">
        <p id="feedback-text" style="margin: 0;"></p>
    </div>

    </div>{{-- /puzzle-main --}}

    <!-- Mission console: health, star rating, XP at stake, hint and an event log -->
    <aside class="mission-console" id="mission-console" aria-label="Mission status"
           data-max="{{ (int) $puzzle->max_attempts }}"
           data-used="{{ (int) $attemptsUsed }}"
           data-try-wrong="{{ (int) $tryAttempts }}"
           data-try-hint="{{ $tryHint ? 1 : 0 }}"
           data-points="{{ $worth }}"
           data-penalty="{{ (int) $hintPenalty }}"
           data-penalty-paid="{{ $penaltyPaid ? 1 : 0 }}"
           data-solved="{{ $solved ? 1 : 0 }}"
           data-health="{{ (int) ($linkHealth ?? 100) }}"
           data-mission="{{ ($hasMission ?? false) ? 1 : 0 }}"
           data-free-after="{{ $freeAfter }}">
        <header class="mc-head">
            <span class="mc-head-title"><i class="mc-dot" aria-hidden="true"></i> Operator status</span>
            <span class="mc-head-task">{{ $typeLabel }}</span>
        </header>

        <section class="mc-card mc-health" id="mc-health" aria-labelledby="mc-health-label">
            <div class="mc-card-top">
                <span class="mc-label" id="mc-health-label"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i> {{ ($hasMission ?? false) ? 'Mission health' : 'Link health' }} <span class="mc-safe" id="mc-safe" hidden>Safe mode</span></span>
                <span class="mc-hp"><b id="mc-hp-num">{{ (int) ($linkHealth ?? 100) }}</b><small>/100</small></span>
            </div>
            <div class="mc-stars" id="mc-stars" role="img" aria-label="3 of 3 stars at this health">
                <span class="mc-star"><i class="fa-solid fa-star" aria-hidden="true"></i></span><span class="mc-star"><i class="fa-solid fa-star" aria-hidden="true"></i></span><span class="mc-star"><i class="fa-solid fa-star" aria-hidden="true"></i></span>
            </div>
            <div class="mc-bar" id="mc-bar" role="progressbar" aria-labelledby="mc-health-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100">
                <div class="mc-bar-ghost" id="mc-bar-ghost"></div>
                <div class="mc-bar-fill" id="mc-bar-fill"></div>
                <div class="mc-bar-notches" id="mc-bar-notches" aria-hidden="true"></div>
            </div>
            <p class="mc-sub" id="mc-health-sub">Wrong answers cost link health.</p>
            <details class="mc-rules">
                <summary>How health and stars work</summary>
                <ul>
                    <li>The bar is split in three segments. Each segment is one star, and the stars left when you solve the last task are the mission's rating.</li>
                    @if ($hasMission ?? false)
                        <li>It is one bar for the whole mission: damage carries over from task to task, and restarting the mission refills it.</li>
                    @endif
                    <li>A wrong answer does damage by how wrong it was (a near miss costs about a third of a total miss), and repeat misses on a task hit harder (×1.15, ×1.3, up to ×1.45).</li>
                    <li>The bar never hits zero: at the floor you are in safe mode and can still finish for one star.</li>
                    @if ((int) $puzzle->max_attempts > 0)
                        <li>This task also has a limit of {{ (int) $puzzle->max_attempts }} attempts.</li>
                    @endif
                    <li>Health and stars never change your XP. Hints cost XP, not health.</li>
                </ul>
            </details>
        </section>

        <section class="mc-card mc-xp" id="mc-xp" aria-labelledby="mc-xp-label">
            <div class="mc-card-top">
                <span class="mc-label" id="mc-xp-label"><i class="fa-solid fa-bolt" aria-hidden="true"></i> <span id="mc-xp-title">XP at stake</span></span>
                <span class="mc-xp-value"><b id="mc-xp-num">{{ $worth }}</b> <small>XP</small></span>
            </div>
            <p class="mc-sub" id="mc-xp-sub">Earned once, when you solve it.</p>
        </section>

        @if (filled($puzzle->hint))
            <section class="mc-card mc-hint" id="mc-hint" aria-labelledby="mc-hint-label">
                <div class="mc-card-top">
                    <span class="mc-label" id="mc-hint-label"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Hint</span>
                    <span class="mc-hint-state" id="mc-hint-state"></span>
                </div>
                @if ($hintRevealed && empty($isPreview))
                    <p id="hint-text" class="hint-text">{{ $puzzle->hint }}</p>
                @else
                    <p class="mc-sub" id="hint-lead">Stuck? A hint costs <strong>{{ $hintPenalty }} XP</strong>, taken the moment you reveal it. It becomes free after {{ $freeAfter }} wrong answers.</p>
                    <button type="button" id="hint-button" class="button mc-hint-button" data-free="{{ $hintFree ? 'true' : 'false' }}">
                        <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                        <span id="hint-button-label">{{ $hintFree ? 'Reveal free hint' : 'Reveal hint' }}</span>
                        <span class="mc-cost" id="hint-cost" @if ($hintFree) hidden @endif>−{{ $hintPenalty }} XP</span>
                    </button>
                    <div id="hint-confirm" class="hint-confirm" role="dialog" aria-label="Confirm hint" hidden>
                        <p>Reveal the hint for <strong>−{{ $hintPenalty }} XP</strong>? It comes off straight away. Hints never cost health.</p>
                        <div class="hint-confirm-actions">
                            <button type="button" class="button" data-hint-yes>Pay −{{ $hintPenalty }} XP</button>
                            <button type="button" class="button secondary" data-hint-no>Keep trying</button>
                        </div>
                    </div>
                    <p id="hint-text" class="hint-text" hidden></p>
                @endif
            </section>
        @endif

        <section class="mc-card mc-log-card" aria-labelledby="mc-log-label">
            <div class="mc-card-top"><span class="mc-label" id="mc-log-label"><i class="fa-solid fa-terminal" aria-hidden="true"></i> Event log</span></div>
            <ul class="mc-log" id="mc-log" aria-live="polite"></ul>
        </section>
    </aside>
    </div>{{-- /puzzle-layout --}}


</div>

<style>
@keyframes phaserSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.55.2/dist/phaser.min.js"></script>
    <script src="{{ asset('js/puzzles/puzzleUtils.js') }}"></script>
    <script src="{{ asset('js/puzzles/missionConsole.js') }}"></script>

    <script>
        window.isPreview    = {!! json_encode(!empty($isPreview)) !!};
        window.inRun        = {!! json_encode($inRun ?? false) !!};
        window.puzzleData   = {!! json_encode(array_merge(
            $puzzle->only(['puzzle_id', 'topic_id', 'puzzle_type', 'question_text', 'instruction', 'points', 'max_attempts']),
            ['attempts_used' => $attemptsUsed ?? 0]
        )) !!};
        window.hintUrl      = @if(!empty($isPreview)) null @else "{{ route('puzzles.hint', $puzzle->puzzle_id) }}" @endif;
        @if (!empty($isPreview))
            window.previewHint = {!! json_encode($puzzle->hint) !!};
        @endif
        window.activityData = {!! json_encode($puzzle->getSafeActivityData()) !!};
        window.submitUrl    = @if(!empty($isPreview)) "{{ route('lecturer.missions.puzzles.preview.submit', $puzzle->puzzle_id) }}" @else "{{ route('puzzles.submit', $puzzle->puzzle_id) }}" @endif;
    </script>

    @if ($puzzle->puzzle_type === 'identification')
        <script src="{{ asset('js/puzzles/TemplateIdentification.js') }}"></script>
    @elseif ($puzzle->puzzle_type === 'matching')
        <script src="{{ asset('js/puzzles/TemplateMatching.js') }}"></script>
    @elseif ($puzzle->puzzle_type === 'sequencing')
        <script src="{{ asset('js/puzzles/TemplateSequencing.js') }}"></script>
    @elseif ($puzzle->puzzle_type === 'classification')
        <script src="{{ asset('js/puzzles/TemplateClassification.js') }}"></script>
    @elseif ($puzzle->puzzle_type === 'drag_and_drop')
        <script src="{{ asset('js/puzzles/DeviceMapper.js') }}"></script>
    @elseif ($puzzle->puzzle_type === 'scenario')
        <script src="{{ asset('js/puzzles/TemplateScenario.js') }}"></script>
    @else
        <script src="{{ asset('js/puzzles/DeviceMapper.js') }}"></script>
    @endif
@endsection
