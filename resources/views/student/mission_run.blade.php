@extends('layouts.run')

@section('content')
<div id="run-root" data-state="boot">
    {{-- Screen-reader announcements for everything the HUD shows visually --}}
    <div id="run-live" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

    <header id="hud" class="hud">
        <div class="hud-left">
            <span class="hud-ticket" id="hud-ticket"></span>
            <span class="hud-title" id="hud-title"></span>
        </div>

        <div class="hud-map-wrap">
            <svg id="hud-map" class="hud-map" role="img" aria-label="Mission network map"></svg>
        </div>

        <div class="hud-right">
            <div class="hud-stat hud-streak" id="hud-streak" title="First-try streak">
                <i class="fa-solid fa-fire" aria-hidden="true"></i><span>x0</span>
            </div>
            <div class="hud-stat hud-xp" title="XP earned in this run">
                <i class="fa-solid fa-bolt" aria-hidden="true"></i><span id="hud-xp">0</span><small>XP</small>
            </div>
            <div class="hud-stat hud-ttl" id="hud-ttl" hidden title="Time to live"></div>
            <button type="button" class="hud-btn" id="hud-mute" aria-pressed="false" title="Mute sound effects">
                <i class="fa-solid fa-volume-high" aria-hidden="true"></i><span class="visually-hidden">Sound effects</span>
            </button>
            <button type="button" class="hud-btn hud-exit" id="hud-exit">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Exit</span>
            </button>
        </div>
    </header>

    <main class="run-stage">
        {{-- 1. Boot --}}
        <section class="run-screen" id="scr-boot" data-screen="boot" aria-labelledby="boot-heading">
            <h1 id="boot-heading" class="visually-hidden">Connecting to the lab</h1>
            <div class="terminal-card">
                <div class="terminal-bar"><span></span><span></span><span></span><em>lab-console</em></div>
                <pre id="boot-lines" class="terminal-body"></pre>
            </div>
            <div class="countdown" id="countdown" aria-hidden="true"></div>
            <button type="button" class="link-btn" id="boot-skip">Skip intro</button>
        </section>

        {{-- 2. Ticket briefing --}}
        <section class="run-screen" id="scr-ticket" data-screen="ticket" aria-labelledby="ticket-heading">
            <div class="ticket-card">
                <div class="ticket-head">
                    <span class="ticket-code" id="ticket-code"></span>
                    <span class="ticket-status">OPEN</span>
                </div>
                <h1 id="ticket-heading" class="ticket-title"></h1>
                <div class="narrator">
                    <div class="narrator-avatar" aria-hidden="true">
                        <svg viewBox="0 0 64 64" width="56" height="56">
                            <circle cx="32" cy="32" r="31" fill="#0e7490" opacity=".25"/>
                            <circle cx="32" cy="26" r="11" fill="#67e8f9"/>
                            <path d="M12 56c3-11 11-17 20-17s17 6 20 17" fill="#22d3ee"/>
                            <path d="M19 27a13 13 0 0 1 26 0" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                            <rect x="15" y="25" width="6" height="9" rx="2" fill="#e2e8f0"/>
                            <rect x="43" y="25" width="6" height="9" rx="2" fill="#e2e8f0"/>
                            <path d="M46 33c0 5-4 7-8 7" fill="none" stroke="#e2e8f0" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="bubble">
                        <strong id="narrator-name"></strong>
                        <p id="ticket-text" class="typed"></p>
                    </div>
                </div>
                <ol class="ticket-tasks" id="ticket-tasks"></ol>
                <button type="button" class="run-cta" id="ticket-start">
                    <i class="fa-solid fa-play" aria-hidden="true"></i> Start mission
                </button>
            </div>
        </section>

        {{-- 3. Play: each activity loads in its own page --}}
        <section class="run-screen" id="scr-play" data-screen="play" aria-labelledby="play-heading">
            <h1 id="play-heading" class="visually-hidden">Current task</h1>
            <div class="frame-wrap">
                <div class="frame-loader" id="frame-loader"><span class="spinner"></span>Loading task…</div>
                <iframe id="run-frame" title="Current task" loading="eager"></iframe>
            </div>
        </section>

        {{-- 4. Next hop transition --}}
        <section class="run-screen" id="scr-hop" data-screen="hop" aria-labelledby="hop-text">
            <div class="hop-card">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <h1 id="hop-text"></h1>
                <p id="hop-sub"></p>
            </div>
        </section>

        {{-- 5. Debrief --}}
        <section class="run-screen" id="scr-debrief" data-screen="debrief" aria-labelledby="debrief-heading">
            <div class="debrief-card">
                <div class="stamp" id="debrief-stamp"></div>
                <h1 id="debrief-heading" class="debrief-title"></h1>
                <p class="debrief-text" id="debrief-text"></p>
                <div class="stars" id="debrief-stars" aria-label=""></div>
                <dl class="debrief-stats">
                    <div><dt>Time</dt><dd id="stat-time">—</dd></div>
                    <div><dt>First-try accuracy</dt><dd id="stat-accuracy">—</dd></div>
                    <div><dt>Health left</dt><dd id="stat-health">—</dd></div>
                    <div><dt>Hints used</dt><dd id="stat-hints">—</dd></div>
                </dl>
                <div class="debrief-xp">
                    <span><i class="fa-solid fa-bolt" aria-hidden="true"></i> <strong id="debrief-xp">0</strong> XP from this mission</span>
                    <div class="rank-line"><span id="debrief-rank"></span><span id="debrief-rank-pct"></span></div>
                    <div class="rank-bar"><span id="debrief-rank-bar"></span></div>
                </div>
                <div class="promoted" id="debrief-promoted" hidden></div>
                <div class="badges" id="debrief-badges"></div>
                <div class="debrief-actions" id="debrief-actions"></div>
            </div>
        </section>
    </main>

    {{-- Exit confirmation (HTML, not window.confirm) --}}
    <div class="run-modal" id="exit-modal" role="dialog" aria-modal="true" aria-labelledby="exit-title" hidden>
        <div class="run-modal-card">
            <h2 id="exit-title">Leave this mission?</h2>
            <p>Your solved tasks are saved. You can continue from the next unsolved task later.</p>
            <div class="run-modal-actions">
                <button type="button" class="run-cta run-cta--ghost" id="exit-stay">Keep playing</button>
                <a class="run-cta run-cta--danger" id="exit-leave" href="#">Exit to topic</a>
            </div>
        </div>
    </div>

    {{-- Shown when a task can no longer be attempted (attempt limit reached) --}}
    <div class="run-modal" id="locked-modal" role="dialog" aria-modal="true" aria-labelledby="locked-title" hidden>
        <div class="run-modal-card">
            <h2 id="locked-title">This task is locked</h2>
            <p id="locked-message"></p>
            <div class="run-modal-actions">
                <a class="run-cta run-cta--ghost" id="locked-review" href="#">Review topic</a>
                <button type="button" class="run-cta" id="locked-skip">Skip to next task</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>window.runConfig = @json($runConfig);</script>
    <script src="{{ asset('js/run/mission-run.js') }}"></script>
@endsection
