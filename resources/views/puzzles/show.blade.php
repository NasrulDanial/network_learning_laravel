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

        /* Scanline raster effect across laboratory screen */
        #phaser-game::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                to bottom,
                transparent 0px,
                transparent 2px,
                rgba(0, 0, 0, 0.12) 2px,
                rgba(0, 0, 0, 0.12) 4px
            );
            pointer-events: none;
            border-radius: inherit;
        }
    </style>
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Mission Telemetry Header Card --}}
    <div class="bg-slate-900/90 border border-slate-700/80 rounded-2xl shadow-2xl p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5 font-mono text-xs text-cyan-400">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>MISSION_NODE: #{{ $puzzle->puzzle_id }} // {{ strtoupper($puzzle->puzzle_type) }}_SIMULATION</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-100 tracking-tight leading-snug">
                    {{ $puzzle->instruction }}
                </h1>
                <p class="text-slate-400 mt-2 text-sm">
                    Configure network nodes and verify topology convergence before submitting telemetry.
                </p>
            </div>

            {{-- Network XP Telemetry Counter --}}
            <div class="flex items-center gap-2 bg-slate-800/90 border border-amber-400/40 px-5 py-2.5 rounded-xl shadow-[0_0_15px_rgba(251,191,36,0.25)] select-none">
                <i class="fa-solid fa-bolt text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.7)]" aria-hidden="true"></i>
                <span class="font-mono font-bold text-amber-300 text-base tracking-wide">+{{ $puzzle->points }} Network XP</span>
            </div>
        </div>
    </div>

    {{-- Network Lab Rack Terminal Wrapper --}}
    <div class="relative rounded-2xl p-2 bg-gradient-to-b from-slate-800 via-slate-900 to-black shadow-[0_0_40px_rgba(0,0,0,0.8),inset_0_1px_1px_rgba(255,255,255,0.1)] mb-6">
        <!-- Rack Frame Header -->
        <div class="flex items-center justify-between px-4 py-2 bg-slate-950/80 rounded-t-xl border-b border-slate-800 text-xs font-mono text-slate-400">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>LINK_ESTABLISHED: eth0 [1 Gbps Full-Duplex]</span>
            </span>
            <span class="text-slate-500">ENGINE: PHASER 3 // AUTO-SCALE</span>
        </div>

        <!-- Phaser Mount -->
        <div id="phaser-game" class="w-full min-h-[440px] bg-slate-950 rounded-b-xl overflow-hidden relative">
            <div id="game-loader" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-slate-950 z-20">
                <div class="w-12 h-12 border-4 border-slate-700 border-t-cyan-400 rounded-full animate-spin"></div>
                <p class="font-mono text-xs text-cyan-400 tracking-widest animate-pulse">
                    INITIALIZING VIRTUAL TOPOLOGY LAB...
                </p>
            </div>
        </div>
    </div>

    {{-- Diagnostic Feedback Panel --}}
    <div id="feedback-panel"
         class="hidden mt-4 p-4 rounded-xl border-2 font-medium backdrop-blur-md transition-all duration-300 ease-out text-sm leading-relaxed">
        <p id="feedback-text"></p>
    </div>

</div>
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
