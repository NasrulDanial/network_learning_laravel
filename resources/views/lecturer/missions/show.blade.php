@extends('layouts.app')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('lecturer.missions.index', ['topic_id' => $mission->topic_id]) }}" style="font-size: 0.85rem; color: var(--nl-cyan, #22d3ee); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;">
        <i class="fa-solid fa-arrow-left"></i> Back to Mission List
    </a>

    <!-- Mission Header Card -->
    <div style="background: rgba(13, 33, 56, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 24px; padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.3); display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 280px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span class="eyebrow" style="color: var(--nl-cyan, #22d3ee); margin-bottom: 0;">
                    Stage {{ $mission->topic->topic_order ?? 1 }}: {{ $mission->topic->title ?? 'Topic' }} &bull; Mission #{{ $mission->mission_order }}
                </span>
                @if ($mission->status === 'published')
                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35); color: #4ade80; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;">
                        Published
                    </span>
                @else
                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; background: rgba(148, 163, 184, 0.15); border: 1px solid rgba(148, 163, 184, 0.35); color: #94a3b8; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;">
                        Draft
                    </span>
                @endif
            </div>

            <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0 0 8px 0; color: #FFFFFF; letter-spacing: -0.02em;">
                {{ $mission->title }}
            </h1>

            @if ($mission->objective)
                <p style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7); margin: 0 0 14px 0; line-height: 1.5; max-width: 680px;">
                    {{ $mission->objective }}
                </p>
            @endif

            <!-- Metadata Badges -->
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 0.82rem;">
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.8);">
                    <i class="fa-solid fa-bullseye" style="color: var(--nl-cyan, #22d3ee);"></i>
                    Pass Mark: <strong>{{ $mission->pass_mark }}%</strong>
                </span>

                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); color: #c084fc; font-weight: 700;">
                    <i class="fa-solid fa-bolt"></i>
                    +{{ $mission->xp_reward }} XP Reward
                </span>

                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.8);">
                    <i class="fa-solid fa-gauge"></i>
                    Difficulty: <strong style="text-transform: capitalize;">{{ $mission->difficulty }}</strong>
                </span>

                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.8);">
                    <i class="fa-solid fa-puzzle-piece" style="color: var(--nl-cyan, #22d3ee);"></i>
                    {{ $mission->puzzles->count() }} Activities
                </span>
            </div>
        </div>

        <!-- Header Actions -->
        <div style="display: flex; align-items: center; gap: 10px;">
            <a
                href="{{ route('lecturer.missions.edit', $mission->mission_id) }}"
                class="button secondary"
                style="padding: 10px 18px; font-size: 0.85rem; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"
            >
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Mission</span>
            </a>

            <a
                href="{{ route('lecturer.missions.puzzles.create', $mission->mission_id) }}"
                class="button"
                style="padding: 10px 20px; font-size: 0.85rem; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 0 15px rgba(34, 211, 238, 0.25);"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add Puzzle Activity</span>
            </a>
        </div>
    </div>
</div>

@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #4ade80; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<!-- Puzzle Activities List -->
<div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan, #22d3ee); margin-bottom: 2px; display: inline-block;">Interactive Challenges</span>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #FFFFFF;">Puzzle Sequence in this Mission</h2>
        </div>
        <span style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.5);">
            Completed sequentially by students. Server evaluates all answers.
        </span>
    </div>

    @if ($mission->puzzles->count() > 0)
        <div style="display: flex; flex-direction: column; gap: 14px;">
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

            @foreach ($mission->puzzles as $puzzle)
                @php
                    $meta = $typeLabels[$puzzle->puzzle_type] ?? ['label' => $puzzle->puzzle_type, 'icon' => 'fa-puzzle-piece', 'color' => '#22d3ee'];
                @endphp
                <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; transition: all 0.3s ease;">
                    <!-- Left: Order & Meta -->
                    <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 280px;">
                        <div style="font-size: 1.15rem; font-weight: 800; color: var(--nl-cyan, #22d3ee); min-width: 32px; text-align: center;">
                            #{{ $puzzle->activity_order ?? $loop->iteration }}
                        </div>

                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 6px; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: {{ $meta['color'] }}; font-size: 0.76rem; font-weight: 700;">
                                    <i class="fa-solid {{ $meta['icon'] }}"></i>
                                    {{ $meta['label'] }}
                                </span>

                                <span style="font-size: 0.75rem; color: #a855f7; font-weight: 700; display: inline-flex; align-items: center; gap: 3px;">
                                    <i class="fa-solid fa-bolt" style="font-size: 0.7rem;"></i>
                                    {{ $puzzle->points }} pts
                                </span>

                                @if ($puzzle->status === 'published')
                                    <span style="padding: 2px 7px; border-radius: 4px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; font-size: 0.7rem; font-weight: 600;">
                                        Live
                                    </span>
                                @else
                                    <span style="padding: 2px 7px; border-radius: 4px; background: rgba(148, 163, 184, 0.15); border: 1px solid rgba(148, 163, 184, 0.3); color: #94a3b8; font-size: 0.7rem; font-weight: 600;">
                                        Draft
                                    </span>
                                @endif
                            </div>

                            <div style="font-weight: 700; color: #FFFFFF; font-size: 0.98rem; margin-bottom: 2px;">
                                {{ $puzzle->question_text }}
                            </div>

                            @if ($puzzle->instruction)
                                <div style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.6); max-width: 500px; line-height: 1.3;">
                                    {{ Str::limit($puzzle->instruction, 85) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Actions -->
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <!-- Safe Inline Lecturer Preview Drawer (Blueprint B3) -->
                        <button
                            type="button"
                            class="button secondary"
                            style="padding: 8px 14px; font-size: 0.82rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;"
                            onclick="openSheetModal('Preview: {{ addslashes($puzzle->question_text) }}', 'LECTURER SANDBOX // DOES NOT COUNT', '{{ route('lecturer.missions.puzzles.preview', $puzzle->puzzle_id) }}', true)"
                            title="Open inline sandbox preview (Zero XP, Zero attempts logged)"
                        >
                            <i class="fa-solid fa-eye" style="color: var(--nl-cyan, #22d3ee);"></i>
                            <span>Inline Preview</span>
                        </button>

                        <!-- Toggle Status -->
                        <form method="POST" action="{{ route('lecturer.missions.puzzles.toggle', $puzzle->puzzle_id) }}" style="display: inline;">
                            @csrf
                            <button
                                type="submit"
                                class="button secondary"
                                style="padding: 8px 12px; font-size: 0.82rem; border-radius: 10px; cursor: pointer;"
                                title="{{ $puzzle->status === 'published' ? 'Switch to Draft' : 'Publish Activity' }}"
                            >
                                <i class="fa-solid {{ $puzzle->status === 'published' ? 'fa-toggle-on' : 'fa-toggle-off' }}" style="color: {{ $puzzle->status === 'published' ? '#4ade80' : '#94a3b8' }};"></i>
                            </button>
                        </form>

                        <!-- Edit -->
                        <a
                            href="{{ route('lecturer.missions.puzzles.edit', $puzzle->puzzle_id) }}"
                            class="button secondary"
                            style="padding: 8px 12px; font-size: 0.82rem; border-radius: 10px; text-decoration: none;"
                            title="Edit activity"
                        >
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>

                        <!-- Delete -->
                        <form
                            method="POST"
                            action="{{ route('lecturer.missions.puzzles.destroy', $puzzle->puzzle_id) }}"
                            onsubmit="return confirm('Are you sure you want to delete this puzzle activity?');"
                            style="display: inline;"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="button danger"
                                style="padding: 8px 12px; font-size: 0.82rem; border-radius: 10px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;"
                                title="Delete activity"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State with quick activity buttons -->
        <div style="text-align: center; padding: 48px 24px; color: rgba(255, 255, 255, 0.6);">
            <div style="font-size: 2.8rem; margin-bottom: 12px; opacity: 0.4;">
                <i class="fa-solid fa-puzzle-piece"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">No puzzle activities created for this mission yet</h3>
            <p style="font-size: 0.88rem; max-width: 520px; margin: 0 auto 24px; line-height: 1.4;">
                Build interactive networking puzzles using code-free visual builders. Select a puzzle template below to add your first activity:
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; max-width: 800px; margin: 0 auto;">
                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => 'identification']) }}"
                    style="padding: 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-decoration: none; text-align: left; transition: all 0.3s ease; display: block;"
                    onmouseover="this.style.background='rgba(56, 189, 248, 0.1)'; this.style.borderColor='rgba(56, 189, 248, 0.3)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.04)'; this.style.borderColor='rgba(255, 255, 255, 0.08)';"
                >
                    <div style="color: #38bdf8; font-size: 1.25rem; margin-bottom: 8px;"><i class="fa-solid fa-eye"></i></div>
                    <div style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem; margin-bottom: 3px;">1. Identification</div>
                    <div style="font-size: 0.78rem; color: rgba(255, 255, 255, 0.5);">Pick correct hardware / concept from options</div>
                </a>

                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => 'matching']) }}"
                    style="padding: 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-decoration: none; text-align: left; transition: all 0.3s ease; display: block;"
                    onmouseover="this.style.background='rgba(167, 139, 250, 0.1)'; this.style.borderColor='rgba(167, 139, 250, 0.3)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.04)'; this.style.borderColor='rgba(255, 255, 255, 0.08)';"
                >
                    <div style="color: #a78bfa; font-size: 1.25rem; margin-bottom: 8px;"><i class="fa-solid fa-arrows-split-up-and-left"></i></div>
                    <div style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem; margin-bottom: 3px;">2. Matching</div>
                    <div style="font-size: 0.78rem; color: rgba(255, 255, 255, 0.5);">Connect protocols, ports, and definitions</div>
                </a>

                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => 'sequencing']) }}"
                    style="padding: 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-decoration: none; text-align: left; transition: all 0.3s ease; display: block;"
                    onmouseover="this.style.background='rgba(250, 204, 21, 0.1)'; this.style.borderColor='rgba(250, 204, 21, 0.3)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.04)'; this.style.borderColor='rgba(255, 255, 255, 0.08)';"
                >
                    <div style="color: #facc15; font-size: 1.25rem; margin-bottom: 8px;"><i class="fa-solid fa-arrow-down-1-9"></i></div>
                    <div style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem; margin-bottom: 3px;">3. Sequencing</div>
                    <div style="font-size: 0.78rem; color: rgba(255, 255, 255, 0.5);">Arrange handshakes, encapsulations in order</div>
                </a>

                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => 'classification']) }}"
                    style="padding: 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-decoration: none; text-align: left; transition: all 0.3s ease; display: block;"
                    onmouseover="this.style.background='rgba(52, 211, 153, 0.1)'; this.style.borderColor='rgba(52, 211, 153, 0.3)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.04)'; this.style.borderColor='rgba(255, 255, 255, 0.08)';"
                >
                    <div style="color: #34d399; font-size: 1.25rem; margin-bottom: 8px;"><i class="fa-solid fa-folder-tree"></i></div>
                    <div style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem; margin-bottom: 3px;">4. Classification</div>
                    <div style="font-size: 0.78rem; color: rgba(255, 255, 255, 0.5);">Sort items into OSI layers or network bins</div>
                </a>

                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => 'drag_and_drop']) }}"
                    style="padding: 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-decoration: none; text-align: left; transition: all 0.3s ease; display: block;"
                    onmouseover="this.style.background='rgba(244, 114, 182, 0.1)'; this.style.borderColor='rgba(244, 114, 182, 0.3)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.04)'; this.style.borderColor='rgba(255, 255, 255, 0.08)';"
                >
                    <div style="color: #f472b6; font-size: 1.25rem; margin-bottom: 8px;"><i class="fa-solid fa-hand-pointer"></i></div>
                    <div style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem; margin-bottom: 3px;">5. Drag & Drop</div>
                    <div style="font-size: 0.78rem; color: rgba(255, 255, 255, 0.5);">Place devices onto layers or network zones</div>
                </a>

                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => 'scenario']) }}"
                    style="padding: 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-decoration: none; text-align: left; transition: all 0.3s ease; display: block;"
                    onmouseover="this.style.background='rgba(251, 146, 60, 0.1)'; this.style.borderColor='rgba(251, 146, 60, 0.3)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.04)'; this.style.borderColor='rgba(255, 255, 255, 0.08)';"
                >
                    <div style="color: #fb923c; font-size: 1.25rem; margin-bottom: 8px;"><i class="fa-solid fa-code-branch"></i></div>
                    <div style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem; margin-bottom: 3px;">6. Scenario Decision</div>
                    <div style="font-size: 0.78rem; color: rgba(255, 255, 255, 0.5);">Branching situational troubleshooting choices</div>
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
