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
<div style="max-width: 960px; margin: 0 auto; padding: 12px 0;">

    <!-- Telemetry Header Card -->
    <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px; margin-bottom: 20px; box-shadow: 0 16px 36px rgba(0,0,0,0.4);">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
            <div style="flex: 1; min-width: 260px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e;"></span>
                    <span class="sheet-badge" style="font-size: 0.72rem;">
                        MISSION NODE: #{{ $puzzle->puzzle_id }} // {{ strtoupper($puzzle->puzzle_type) }}_SIMULATION
                    </span>
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: #FFFFFF; margin: 0; letter-spacing: -0.02em;">
                    {{ $puzzle->instruction }}
                </h1>
                <p style="font-size: 0.88rem; color: var(--nl-text-secondary); margin-top: 6px; margin-bottom: 0;">
                    Configure network nodes and verify topology convergence before transmitting telemetry.
                </p>
            </div>

            <!-- Network XP Counter -->
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.35); padding: 10px 18px; border-radius: var(--nl-radius-btn); box-shadow: var(--nl-glow-xp); user-select: none;">
                <i class="fa-solid fa-bolt" style="color: #fbbf24; font-size: 1.1rem;"></i>
                <span style="font-weight: 700; color: #fde047; font-size: 0.95rem;">+{{ $puzzle->points }} Network XP</span>
            </div>
        </div>
    </div>

    <!-- Network Lab Terminal Wrapper -->
    <div style="position: relative; border-radius: var(--nl-radius-panel); padding: 6px; background: linear-gradient(180deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), inset 0 1px 1px rgba(255, 255, 255, 0.1); margin-bottom: 20px;">
        <!-- Terminal Header Bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; background: rgba(7, 12, 22, 0.85); border-radius: 18px 18px 0 0; border-bottom: 1px solid rgba(255, 255, 255, 0.06); font-size: 0.75rem; color: var(--nl-text-tertiary);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="display: flex; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                </div>
                <span style="color: #22d3ee; font-weight: 600; margin-left: 6px;">eth0 [1 Gbps Full-Duplex]</span>
            </div>
            <span>ENGINE: PHASER 3 // CANVAS AUTO-SCALE</span>
        </div>

        <!-- Phaser Mount Container -->
        <div id="phaser-game" style="width: 100%; min-height: 460px; background: #070d18; border-radius: 0 0 18px 18px; overflow: hidden; position: relative;">
            <div id="game-loader" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; background: #070d18; z-index: 20;">
                <div style="width: 44px; height: 44px; border: 3px solid rgba(255, 255, 255, 0.1); border-top-color: var(--nl-cyan); border-radius: 50%; animation: phaserSpin 0.9s linear infinite;"></div>
                <span style="font-size: 0.78rem; font-weight: 600; color: var(--nl-cyan); letter-spacing: 0.1em;">
                    INITIALIZING VIRTUAL TOPOLOGY LAB...
                </span>
            </div>
        </div>
    </div>

    <!-- Diagnostic Feedback Panel -->
    <div id="feedback-panel"
         style="display: none; margin-top: 16px; padding: 16px; border-radius: var(--nl-radius-card); border: 2px solid transparent; font-weight: 500; font-size: 0.9rem; line-height: 1.5; backdrop-filter: var(--nl-glass-blur); transition: var(--nl-transition);">
        <p id="feedback-text" style="margin: 0;"></p>
    </div>

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

    <script>
        const puzzleData = @json($puzzle->only(['puzzle_id', 'topic_id', 'puzzle_type', 'instruction', 'points']));
        const submitUrl  = "{{ route('puzzles.submit', $puzzle->puzzle_id) }}";
    </script>

    @if ($puzzle->puzzle_type === 'drag_and_drop' || $puzzle->puzzle_type === 'matching')
        <script src="{{ asset('js/puzzles/DeviceMapper.js') }}"></script>
    @elseif ($puzzle->puzzle_type === 'sequencing')
        <script src="{{ asset('js/puzzles/OsiSequencer.js') }}"></script>
    @endif
@endsection
