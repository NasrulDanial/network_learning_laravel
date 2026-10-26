@extends('layouts.app')

@section('content')
@php
    $points = (int) ($totalPoints ?? 0);
    $progressPct = round((float) ($overallProgress ?? 0));
    $completedCount = (int) ($completedTopics ?? 0);

    // Clearance level calculations (500 XP per tier)
    $clearanceLevel = (int) floor($points / 500) + 1;
    $clearanceRanks = [
        1 => 'Clearance Level 1: Network Cadet',
        2 => 'Clearance Level 2: Packet Analyst',
        3 => 'Clearance Level 3: Infrastructure Technician',
        4 => 'Clearance Level 4: Network Security Specialist',
        5 => 'Clearance Level 5: Principal Network Architect',
    ];
    $rankTitle = $clearanceRanks[min($clearanceLevel, 5)] ?? 'Clearance Level 1: Network Cadet';
    $nextLevelXp = $clearanceLevel * 500;
    $currentTierXp = $points % 500;
    $tierProgressPct = min(100, round(($currentTierXp / 500) * 100));

    // SVG Circular Ring calculation (radius 52, perimeter = 2 * PI * 52 ≈ 326.7)
    $circumference = 326.7;
    $strokeOffset = $circumference - ($circumference * ($progressPct / 100));
@endphp

<!-- macOS Widget Grid -->
<div class="widget-grid">
    <!-- Hero Widget: Circular Progress & XP Telemetry -->
    <div class="widget-hero">
        <svg style="position: absolute; width: 0; height: 0;">
            <defs>
                <linearGradient id="cyanPurpleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#22D3EE" />
                    <stop offset="100%" stop-color="#8B5CF6" />
                </linearGradient>
            </defs>
        </svg>

        <div style="flex: 1; z-index: 1;">
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 6px;">Operative Telemetry</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.02em;">
                Welcome back, {{ auth()->user()->name }}
            </h1>
            <p style="font-size: 0.92rem; color: var(--nl-text-secondary); max-width: 500px; margin-bottom: 20px;">
                Virtual network topologies operational. All telemetry sensors are synced to your clearance profile.
            </p>

            <div style="display: flex; align-items: center; gap: 24px;">
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); display: block; font-weight: 600;">Network XP</span>
                    <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ number_format($points) }} <span style="font-size: 0.85rem; color: #fbbf24;">XP</span></strong>
                </div>
                <div style="width: 1px; height: 32px; background: var(--nl-glass-border);"></div>
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); display: block; font-weight: 600;">Completed Stages</span>
                    <strong style="font-size: 1.35rem; font-weight: 700; color: #FFFFFF;">{{ $completedCount }} <span style="font-size: 0.85rem; color: #4ade80;">Stages</span></strong>
                </div>
            </div>
        </div>

        <!-- SVG Circular Ring -->
        <div class="xp-ring-container">
            <svg class="xp-ring-svg" viewBox="0 0 120 120">
                <circle class="xp-ring-bg" cx="60" cy="60" r="52" />
                <circle
                    class="xp-ring-fill"
                    cx="60"
                    cy="60"
                    r="52"
                    stroke-dasharray="326.7"
                    stroke-dashoffset="{{ $strokeOffset }}"
                />
            </svg>
            <div class="xp-ring-center">
                <span class="xp-ring-val">{{ $progressPct }}%</span>
                <span class="xp-ring-unit">Convergence</span>
            </div>
        </div>
    </div>

    <!-- Clearance Level Badge Widget -->
    <div class="widget-square">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 8px;">Security Credential</span>
            <div class="clearance-badge-widget" style="margin-top: 8px;">
                <div class="clearance-insignia">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <span class="clearance-tier">Tier {{ $clearanceLevel }}</span>
                    <div class="clearance-name">{{ $rankTitle }}</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--nl-text-secondary); margin-bottom: 6px; font-weight: 600;">
                <span>Tier {{ $clearanceLevel }} Progress</span>
                <span>{{ $currentTierXp }} / 500 XP</span>
            </div>
            <div style="height: 6px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; overflow: hidden;">
                <div style="width: {{ $tierProgressPct }}%; height: 100%; background: var(--nl-gradient-accent); border-radius: 99px; transition: width 0.8s var(--nl-spring);"></div>
            </div>
            <span style="font-size: 0.72rem; color: var(--nl-text-tertiary); display: block; margin-top: 8px;">
                {{ 500 - $currentTierXp }} XP until Tier {{ $clearanceLevel + 1 }} promotion
            </span>
        </div>
    </div>
</div>

<!-- Interactive Missions (Slide-up Bottom Sheet Puzzles) -->
<div style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Simulation Nodes</span>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">Interactive Virtual Labs</h2>
        </div>
        <span style="font-size: 0.82rem; color: var(--nl-text-tertiary);">Direct bottom sheet invocation</span>
    </div>

    <div class="quick-nodes-grid">
        @php
            $archetypeIcons = [
                'identification' => 'fa-eye',
                'matching'       => 'fa-arrows-split-up-and-left',
                'sequencing'     => 'fa-layer-group',
                'classification' => 'fa-folder-tree',
                'drag_and_drop'  => 'fa-network-wired',
                'scenario'       => 'fa-code-branch',
            ];
        @endphp

        @if (isset($recentPuzzles) && $recentPuzzles->count() > 0)
            @foreach ($recentPuzzles as $rp)
                @php
                    $icon = $archetypeIcons[$rp->puzzle_type] ?? 'fa-puzzle-piece';
                @endphp
                <div
                    class="node-card"
                    data-sheet-url="{{ route('puzzles.show', $rp->puzzle_id) }}"
                    data-sheet-title="{{ $rp->question_text }}"
                    data-sheet-badge="{{ strtoupper(str_replace('_', ' ', $rp->puzzle_type)) }} // NODE_{{ $rp->puzzle_id }}"
                    role="button"
                    tabindex="0"
                >
                    <div>
                        <div class="node-icon">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>
                        <h3>{{ $rp->question_text }}</h3>
                        <p>{{ Str::limit($rp->instruction, 85) }}</p>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; font-size: 0.78rem;">
                        <span style="color: #fbbf24; font-weight: 700;">+{{ $rp->points }} Network XP</span>
                        <span style="color: var(--nl-cyan); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                            Launch Sheet <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </span>
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Quick Launch 3: Quiz Hub -->
        <a class="node-card" href="{{ route('student.quiz') }}">
            <div>
                <div class="node-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>Incident Challenges</h3>
                <p>Assess theoretical networking mastery with step-by-step evaluations.</p>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; font-size: 0.78rem;">
                <span style="color: #4ade80; font-weight: 700;">Pass Marks: 80%</span>
                <span style="color: var(--nl-cyan); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                    Enter Hub <i class="fa-solid fa-arrow-right"></i>
                </span>
            </div>
        </a>
    </div>
</div>

<!-- Primary Navigation Cards -->
<div>
    <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Curriculum Pathways</span>
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 16px;">Core Modules</h2>

    <div class="quick-nodes-grid">
        <a class="node-card" href="{{ route('student.learning') }}">
            <div>
                <div class="node-icon">
                    <i class="fa-solid fa-diagram-project"></i>
                </div>
                <h3>Learning Journey</h3>
                <p>Sequential stages covering OSI, TCP/IP, Subnetting, and Routing protocols.</p>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; font-size: 0.78rem;">
                <span style="color: var(--nl-text-tertiary);">Stage-by-stage unlocks</span>
                <span style="color: var(--nl-cyan); font-weight: 600;">View Stages &rarr;</span>
            </div>
        </a>

        <a class="node-card" href="{{ route('student.progress') }}">
            <div>
                <div class="node-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>Telemetry & Records</h3>
                <p>Review quiz attempts, accuracy telemetry, and historical challenge scores.</p>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; font-size: 0.78rem;">
                <span style="color: var(--nl-text-tertiary);">Comprehensive stats</span>
                <span style="color: var(--nl-cyan); font-weight: 600;">Inspect Analytics &rarr;</span>
            </div>
        </a>

        @php
            $firstPuzzleId = isset($recentPuzzles) && $recentPuzzles->isNotEmpty() ? $recentPuzzles->first()->puzzle_id : 1;
        @endphp
        <div
            class="node-card"
            data-sheet-url="{{ route('puzzles.show', $firstPuzzleId) }}"
            data-sheet-title="Quick Diagnostics Sandbox"
            data-sheet-badge="DIAGNOSTIC // LAB"
            role="button"
            tabindex="0"
        >
            <div>
                <div class="node-icon">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <h3>Diagnostics Sandbox</h3>
                <p>Rapidly test network node convergence without affecting clearance rank.</p>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; font-size: 0.78rem;">
                <span style="color: var(--nl-text-tertiary);">Practice environment</span>
                <span style="color: var(--nl-cyan); font-weight: 600;">Launch Sandbox &rarr;</span>
            </div>
        </div>
    </div>
</div>
@endsection
