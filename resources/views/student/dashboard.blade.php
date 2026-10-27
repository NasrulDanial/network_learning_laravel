@extends('layouts.app')

@section('content')
<!-- Hero Bento Panel: Personal Welcome + Career Rank + Primary "Continue Learning" Action (Blueprint C1) -->
<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 28px; margin-bottom: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.3); position: relative; overflow: hidden;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
        <div style="flex: 1; min-width: 280px; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 0;">Operative Telemetry</span>
                <span class="status-pill pass" style="font-size: 0.68rem; padding: 2px 8px;">Active Session</span>
            </div>

            <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0 0 8px 0; color: #FFFFFF; letter-spacing: -0.02em;">
                Welcome back, {{ auth()->user()->name }}
            </h1>

            <p style="font-size: 0.9rem; color: var(--nl-text-secondary); margin: 0 0 20px 0; max-width: 540px; line-height: 1.5;">
                Virtual networking laboratories operational. Continue your progression through sequential computer networking stages.
            </p>

            <!-- Primary "Continue Learning" Action Button (Blueprint C1) -->
            @if (isset($continueTarget) && $continueTarget)
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <a
                        href="{{ $continueTarget['url'] }}"
                        class="button"
                        style="padding: 11px 22px; font-size: 0.9rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 0 20px rgba(34, 211, 238, 0.35);"
                    >
                        <i class="fa-solid fa-play"></i>
                        <span>Continue Learning &bull; Stage {{ $continueTarget['topic_order'] }}</span>
                    </a>
                    <span style="font-size: 0.8rem; color: var(--nl-text-tertiary);">
                        Next Up: <strong style="color: #FFFFFF;">{{ $continueTarget['topic_title'] }}</strong>
                    </span>
                </div>
            @else
                <a
                    href="{{ route('student.learning') }}"
                    class="button"
                    style="padding: 11px 22px; font-size: 0.9rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px;"
                >
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>Open Learning Journey</span>
                </a>
            @endif
        </div>

        <!-- Interactive Rank Card: Clicking opens Rank Progression Modal (Blueprint C1) -->
        <div
            onclick="openRankModal()"
            role="button"
            tabindex="0"
            title="Click to view full Career Rank Progression"
            style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 20px; padding: 22px 24px; min-width: 280px; cursor: pointer; transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease; z-index: 2;"
            onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='var(--nl-cyan)';"
            onmouseout="this.style.transform='none'; this.style.borderColor='rgba(34, 211, 238, 0.3)';"
            onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); openRankModal(); }"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <span class="eyebrow" style="color: var(--nl-cyan); margin: 0; font-size: 0.7rem;">Security Credential</span>
                <span style="font-size: 0.72rem; color: var(--nl-cyan); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                    View Tiers <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(34, 211, 238, 0.15); border: 1px solid rgba(34, 211, 238, 0.3); color: var(--nl-cyan); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                    <i class="fa-solid {{ $rankInfo['current_rank_icon'] ?? 'fa-award' }}"></i>
                </div>
                <div>
                    <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--nl-text-tertiary); font-weight: 700; letter-spacing: 0.06em;">Tier {{ $rankInfo['current_level'] }}</span>
                    <div style="font-weight: 800; color: #FFFFFF; font-size: 0.95rem;">{{ $rankInfo['current_rank_short'] }}</div>
                </div>
            </div>

            <!-- Tier XP Progress Bar -->
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--nl-text-secondary); margin-bottom: 6px; font-weight: 600;">
                    <span>{{ $rankInfo['tier_progress_xp'] }} / 500 XP</span>
                    <span>{{ $rankInfo['tier_progress_pct'] }}%</span>
                </div>
                <div style="height: 6px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; overflow: hidden;">
                    <div style="width: {{ $rankInfo['tier_progress_pct'] }}%; height: 100%; background: var(--nl-gradient-accent); border-radius: 99px;"></div>
                </div>
                <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block; margin-top: 6px;">
                    @if ($rankInfo['is_max_tier'])
                        Maximum Career Tier Achieved
                    @else
                        {{ $rankInfo['xp_remaining'] }} XP to Tier {{ $rankInfo['current_level'] + 1 }}
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Compact Summary Bento Cards (Blueprint C1: 4 Defined Metrics) -->
<div class="widget-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px; gap: 16px;">
    <!-- 1. Total Accumulated XP -->
    <div class="widget-stat-mini">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(251, 191, 36, 0.15); color: #fbbf24; border-color: rgba(251, 191, 36, 0.3);">
            <i class="fa-solid fa-bolt"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Total Experience</span>
            <strong style="font-size: 1.4rem; font-weight: 800; color: #FFFFFF;">{{ number_format($totalPoints) }} <span style="font-size: 0.82rem; color: #fbbf24;">XP</span></strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">puzzles + quizzes</span>
        </div>
    </div>

    <!-- 2. Missions Cleared -->
    <div class="widget-stat-mini">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(34, 211, 238, 0.1); color: var(--nl-cyan); border-color: rgba(34, 211, 238, 0.25);">
            <i class="fa-solid fa-puzzle-piece"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Missions Cleared</span>
            <strong style="font-size: 1.4rem; font-weight: 800; color: #FFFFFF;">{{ (int) $completedMissions }}</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">interactive PBL labs</span>
        </div>
    </div>

    <!-- 3. Average Evaluation Score -->
    <div class="widget-stat-mini">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">
            <i class="fa-solid fa-chart-simple"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Average Score</span>
            <strong style="font-size: 1.4rem; font-weight: 800; color: #FFFFFF;">{{ number_format((float) $averageQuizScore, 0) }}%</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">evaluation accuracy</span>
        </div>
    </div>

    <!-- 4. Badges Earned -->
    <div class="widget-stat-mini">
        <div class="node-icon" style="margin-bottom: 0; background: rgba(168, 85, 247, 0.15); color: #c084fc; border-color: rgba(168, 85, 247, 0.3);">
            <i class="fa-solid fa-award"></i>
        </div>
        <div>
            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--nl-text-tertiary); font-weight: 600; display: block;">Honors Earned</span>
            <strong style="font-size: 1.4rem; font-weight: 800; color: #FFFFFF;">{{ (int) $badgesEarnedCount }}</strong>
            <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block;">achievements unlocked</span>
        </div>
    </div>
</div>

<!-- Split Section: Quick Virtual Labs (Left) + Latest Achievement / Curriculum Status (Right) -->
<div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: start; margin-bottom: 24px;">

    <!-- Left: Virtual Labs Quick Launch -->
    <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div>
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Interactive Simulations</span>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #FFFFFF;">Recommended Lab Challenges</h2>
            </div>
            <a href="{{ route('student.learning') }}" style="font-size: 0.78rem; color: var(--nl-cyan); text-decoration: none; font-weight: 600;">
                All Stages &rarr;
            </a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
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
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 240px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.25); color: var(--nl-cyan); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                <i class="fa-solid {{ $icon }}"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--nl-cyan); letter-spacing: 0.06em;">
                                    {{ strtoupper(str_replace('_', ' ', $rp->puzzle_type)) }} &bull; Stage {{ $rp->topic->topic_order ?? 1 }}
                                </span>
                                <h3 style="font-size: 0.95rem; font-weight: 700; margin: 2px 0; color: #FFFFFF;">
                                    {{ $rp->question_text }}
                                </h3>
                                <p style="font-size: 0.78rem; color: var(--nl-text-secondary); margin: 0;">
                                    {{ Str::limit($rp->instruction, 85) }}
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #fbbf24;">+{{ $rp->points }} XP</span>
                            <a
                                href="{{ route('puzzles.show', $rp->puzzle_id) }}"
                                class="button"
                                style="padding: 8px 16px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px;"
                            >
                                <i class="fa-solid fa-play"></i>
                                <span>Launch</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Practice Arena Shortcut Card -->
            <div style="background: rgba(168, 85, 247, 0.06); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); color: #c084fc; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #c084fc; letter-spacing: 0.06em;">
                            Optional Theory Assessment
                        </span>
                        <h3 style="font-size: 0.95rem; font-weight: 700; margin: 2px 0; color: #FFFFFF;">
                            Practice Arena & Quizzes
                        </h3>
                        <p style="font-size: 0.78rem; color: var(--nl-text-secondary); margin: 0;">
                            Reinforce theoretical mastery with multiple-choice evaluations across unlocked topics.
                        </p>
                    </div>
                </div>

                <a
                    href="{{ route('student.quiz') }}"
                    class="button secondary"
                    style="padding: 8px 16px; font-size: 0.8rem;"
                >
                    Enter Arena &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Right: Latest Achievement & Overall Convergence -->
    <div style="display: flex; flex-direction: column; gap: 20px;">

        <!-- Latest Achievement Card (Blueprint C1) -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <span class="eyebrow" style="color: #fbbf24; margin: 0; font-size: 0.7rem;">Clearance Milestone</span>
                <a href="{{ route('student.achievements') }}" style="font-size: 0.75rem; color: var(--nl-cyan); text-decoration: none; font-weight: 600;">
                    All Badges &rarr;
                </a>
            </div>

            @if (isset($latestAchievement) && $latestAchievement)
                <div style="display: flex; align-items: center; gap: 14px; background: rgba(251, 191, 36, 0.06); border: 1px solid rgba(251, 191, 36, 0.25); border-radius: 12px; padding: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.35); color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                        <i class="fa-solid {{ $latestAchievement->achievement->badge_icon ?? 'fa-award' }}"></i>
                    </div>
                    <div>
                        <strong style="color: #FFFFFF; font-size: 0.92rem; display: block;">{{ $latestAchievement->achievement->badge_name }}</strong>
                        <p style="font-size: 0.76rem; color: var(--nl-text-secondary); margin: 2px 0 0 0;">
                            {{ $latestAchievement->achievement->description }}
                        </p>
                        <span style="font-size: 0.68rem; color: var(--nl-text-tertiary); display: block; margin-top: 4px;">
                            Earned on {{ \Carbon\Carbon::parse($latestAchievement->date_earned)->format('M d, Y') }}
                        </span>
                    </div>
                </div>
            @else
                <div style="text-align: center; padding: 20px; color: var(--nl-text-tertiary);">
                    <i class="fa-solid fa-trophy" style="font-size: 1.8rem; color: rgba(255,255,255,0.2); margin-bottom: 8px; display: block;"></i>
                    <p style="font-size: 0.8rem; margin: 0;">Complete your first mission or quiz to unlock your first Clearance Honor badge!</p>
                </div>
            @endif
        </div>

        <!-- Curriculum Overall Convergence -->
        <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span class="eyebrow" style="color: var(--nl-cyan); margin: 0; font-size: 0.7rem;">Curriculum Convergence</span>
                <strong style="color: #FFFFFF; font-size: 1.1rem;">{{ round($overallProgress) }}%</strong>
            </div>

            <div style="height: 6px; background: rgba(255,255,255,0.08); border-radius: 99px; overflow: hidden; margin-bottom: 12px;">
                <div style="width: {{ min(100, max(0, $overallProgress)) }}%; height: 100%; background: var(--nl-gradient-accent); border-radius: 99px;"></div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.76rem; color: var(--nl-text-secondary);">
                <span>Completed: <strong>{{ (int) $completedTopics }} Stages</strong></span>
                <a href="{{ route('student.progress') }}" style="color: var(--nl-cyan); text-decoration: none; font-weight: 600;">
                    Telemetry & Records &rarr;
                </a>
            </div>
        </div>

    </div>
</div>

<!-- =====================================================================
     RANK PROGRESSION ACCESSIBLE MODAL DIALOG (Blueprint C1)
     Shows current tier, next rank threshold, XP remaining, and all tiers
     ===================================================================== -->
<div class="nl-modal-backdrop" id="rankProgressionModal">
    <div class="nl-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="rankModalTitle" style="max-width: 580px;">
        <div class="nl-modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(34, 211, 238, 0.15); border: 1px solid rgba(34, 211, 238, 0.35); color: var(--nl-cyan); display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <h3 id="rankModalTitle" style="font-size: 1.15rem; font-weight: 800; color: #FFFFFF; margin: 0;">
                        Career Clearance Progression
                    </h3>
                    <span style="font-size: 0.74rem; color: var(--nl-text-tertiary);">Authoritative NetLearn Experience Tiers</span>
                </div>
            </div>
            <button type="button" class="btn-close-slideover" onclick="closeRankModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="nl-modal-body">
            <!-- Active Rank Summary Card -->
            <div style="background: linear-gradient(135deg, rgba(34, 211, 238, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%); border: 1px solid rgba(34, 211, 238, 0.35); border-radius: 16px; padding: 18px 20px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--nl-cyan); font-weight: 800; letter-spacing: 0.06em;">Current Standing</span>
                        <h4 style="font-size: 1.1rem; font-weight: 800; color: #FFFFFF; margin: 2px 0 0 0;">
                            {{ $rankInfo['current_rank_title'] }}
                        </h4>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.72rem; color: var(--nl-text-tertiary); display: block;">Total XP</span>
                        <strong style="font-size: 1.25rem; font-weight: 800; color: #fbbf24;">{{ number_format($totalPoints) }} XP</strong>
                    </div>
                </div>

                <p style="font-size: 0.8rem; color: var(--nl-text-secondary); margin: 0 0 12px 0; line-height: 1.4;">
                    {{ $rankInfo['current_rank_desc'] }}
                </p>

                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--nl-text-secondary); margin-bottom: 4px; font-weight: 600;">
                        <span>Progress to Tier {{ min(5, $rankInfo['current_level'] + 1) }}</span>
                        <span>{{ $rankInfo['tier_progress_xp'] }} / 500 XP ({{ $rankInfo['tier_progress_pct'] }}%)</span>
                    </div>
                    <div style="height: 6px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; overflow: hidden;">
                        <div style="width: {{ $rankInfo['tier_progress_pct'] }}%; height: 100%; background: var(--nl-gradient-accent); border-radius: 99px;"></div>
                    </div>
                    @if (!$rankInfo['is_max_tier'])
                        <span style="font-size: 0.7rem; color: var(--nl-text-tertiary); display: block; margin-top: 6px;">
                            {{ $rankInfo['xp_remaining'] }} XP remaining to reach {{ $rankInfo['next_tier_xp'] }} XP threshold
                        </span>
                    @endif
                </div>
            </div>

            <!-- All Clearance Tiers Roadmap (Blueprint C1) -->
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 10px; display: block;">All Clearance Tiers</span>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($rankInfo['all_ranks'] as $r)
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid {{ $r['is_current'] ? 'rgba(34, 211, 238, 0.45)' : ($r['is_unlocked'] ? 'rgba(34, 197, 94, 0.3)' : 'rgba(255, 255, 255, 0.06)') }}; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 14px; opacity: {{ $r['is_unlocked'] ? '1' : '0.55' }};">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 34px; height: 34px; border-radius: 8px; background: {{ $r['is_current'] ? 'rgba(34, 211, 238, 0.2)' : ($r['is_unlocked'] ? 'rgba(34, 197, 94, 0.15)' : 'rgba(255,255,255,0.05)') }}; color: {{ $r['is_current'] ? 'var(--nl-cyan)' : ($r['is_unlocked'] ? '#4ade80' : 'rgba(255,255,255,0.4)') }}; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                                <i class="fa-solid {{ $r['icon'] }}"></i>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <strong style="color: #FFFFFF; font-size: 0.88rem;">{{ $r['title'] }}</strong>
                                    @if ($r['is_current'])
                                        <span class="status-pill pass" style="font-size: 0.62rem; padding: 1px 6px;">CURRENT</span>
                                    @elseif ($r['is_unlocked'])
                                        <span style="color: #4ade80; font-size: 0.72rem;"><i class="fa-solid fa-circle-check"></i></span>
                                    @else
                                        <span style="color: rgba(255,255,255,0.4); font-size: 0.72rem;"><i class="fa-solid fa-lock"></i></span>
                                    @endif
                                </div>
                                <span style="font-size: 0.72rem; color: var(--nl-text-tertiary);">
                                    Requirement: {{ $r['min_xp'] }}+ XP &bull; {{ $r['description'] }}
                                </span>
                            </div>
                        </div>

                        <div style="text-align: right; flex-shrink: 0;">
                            <span style="font-size: 0.78rem; font-weight: 700; color: #fbbf24;">
                                {{ $r['min_xp'] }} XP
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Formal Disclaimer (Blueprint C1) -->
            <div style="margin-top: 18px; padding: 12px 14px; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--nl-glass-border); border-radius: 10px; font-size: 0.74rem; color: var(--nl-text-tertiary); line-height: 1.4;">
                <i class="fa-solid fa-circle-info" style="color: var(--nl-cyan); margin-right: 4px;"></i>
                {{ $rankInfo['disclaimer'] }}
            </div>
        </div>

        <div class="nl-modal-footer">
            <button type="button" class="button secondary" onclick="closeRankModal()">
                Close
            </button>
            <a href="{{ route('student.learning') }}" class="button">
                <span>Earn More XP</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openRankModal() {
    const modal = document.getElementById('rankProgressionModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeRankModal() {
    const modal = document.getElementById('rankProgressionModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeRankModal();
    }
});

document.addEventListener('click', (e) => {
    const modal = document.getElementById('rankProgressionModal');
    if (modal && e.target === modal) {
        closeRankModal();
    }
});
</script>
@endsection
