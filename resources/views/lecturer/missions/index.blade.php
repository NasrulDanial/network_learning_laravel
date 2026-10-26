@extends('layouts.app')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <div>
        <span class="eyebrow" style="color: var(--nl-cyan, #22d3ee); margin-bottom: 4px; display: inline-block;">PBL Curriculum Engine</span>
        <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Learning Missions & Puzzle Activities</h1>
        <p style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.6); margin-top: 6px; margin-bottom: 0;">
            Manage puzzle-based learning missions, structured activities, pass criteria, and XP progression.
        </p>
    </div>

    @if ($selectedTopic)
        <div>
            <a
                href="{{ route('lecturer.missions.create', ['topic_id' => $selectedTopic->topic_id]) }}"
                class="button"
                style="padding: 11px 22px; font-size: 0.9rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 0 15px rgba(34, 211, 238, 0.25); text-decoration: none;"
            >
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Add New Mission</span>
            </a>
        </div>
    @endif
</div>

@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #4ade80; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<!-- Topic Filter Bar -->
<div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: rgba(255, 255, 255, 0.7); font-weight: 600;">
        <i class="fa-solid fa-diagram-project" style="color: var(--nl-cyan, #22d3ee);"></i>
        <span>Topic Filter:</span>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1;">
        @foreach ($topics as $t)
            <a
                href="{{ route('lecturer.missions.index', ['topic_id' => $t->topic_id]) }}"
                style="padding: 7px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
                       {{ $selectedTopic && $selectedTopic->topic_id === $t->topic_id
                          ? 'background: linear-gradient(135deg, rgba(34, 211, 238, 0.25), rgba(168, 85, 247, 0.25)); border: 1px solid rgba(34, 211, 238, 0.5); color: #FFFFFF; box-shadow: 0 0 12px rgba(34, 211, 238, 0.2);'
                          : 'background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.7);' }}"
            >
                Stage {{ $t->topic_order }}: {{ $t->title }}
            </a>
        @endforeach
    </div>
</div>

<!-- Missions Table / List Panel -->
<div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan, #22d3ee); margin-bottom: 2px; display: inline-block;">
                @if ($selectedTopic)
                    Stage {{ $selectedTopic->topic_order }}: {{ $selectedTopic->title }}
                @else
                    All Topics
                @endif
            </span>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #FFFFFF;">Configured Missions</h2>
        </div>
        <span style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.5);">
            {{ $missions->count() }} {{ Str::plural('mission', $missions->count()) }} configured
        </span>
    </div>

    @if ($missions->count() > 0)
        <div style="overflow-x: auto;">
            <table class="table-borderless-glass" style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                <thead>
                    <tr style="color: rgba(255, 255, 255, 0.5); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 12px 16px; text-align: left;">Order</th>
                        <th style="padding: 12px 16px; text-align: left;">Mission Details</th>
                        <th style="padding: 12px 16px; text-align: center;">Difficulty</th>
                        <th style="padding: 12px 16px; text-align: center;">Pass Mark</th>
                        <th style="padding: 12px 16px; text-align: center;">XP Reward</th>
                        <th style="padding: 12px 16px; text-align: center;">Activities</th>
                        <th style="padding: 12px 16px; text-align: center;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($missions as $m)
                        <tr style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; transition: all 0.3s ease;">
                            <td style="padding: 16px; font-weight: 700; color: var(--nl-cyan, #22d3ee); font-size: 0.95rem;">
                                #{{ $m->mission_order }}
                            </td>
                            <td style="padding: 16px;">
                                <div style="font-weight: 700; color: #FFFFFF; font-size: 0.98rem; margin-bottom: 4px;">
                                    {{ $m->title }}
                                </div>
                                @if ($m->objective)
                                    <div style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.6); max-width: 380px; line-height: 1.4;">
                                        {{ Str::limit($m->objective, 90) }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                @php
                                    $diffColors = [
                                        'beginner'     => 'rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);',
                                        'intermediate' => 'rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3);',
                                        'advanced'     => 'rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);',
                                    ];
                                @endphp
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: {{ $diffColors[$m->difficulty] ?? 'rgba(255,255,255,0.1); color: #fff;' }}">
                                    {{ $m->difficulty }}
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                <span style="font-weight: 700; color: #FFFFFF; font-size: 0.9rem;">
                                    {{ $m->pass_mark }}%
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                <span style="font-weight: 700; color: #a855f7; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-bolt" style="font-size: 0.8rem;"></i>
                                    +{{ $m->xp_reward }} XP
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 8px; background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.25); color: #22d3ee; font-weight: 700; font-size: 0.82rem;">
                                    {{ $m->puzzles_count }} {{ Str::plural('puzzle', $m->puzzles_count) }}
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                @if ($m->status === 'published')
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 8px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35); color: #4ade80; font-size: 0.78rem; font-weight: 600;">
                                        Published
                                    </span>
                                @else
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 8px; background: rgba(148, 163, 184, 0.15); border: 1px solid rgba(148, 163, 184, 0.35); color: #94a3b8; font-size: 0.78rem; font-weight: 600;">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 16px; text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <a
                                        href="{{ route('lecturer.missions.show', $m->mission_id) }}"
                                        class="button"
                                        style="padding: 7px 12px; font-size: 0.8rem; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"
                                        title="Manage activities within this mission"
                                    >
                                        <i class="fa-solid fa-puzzle-piece"></i>
                                        <span>Activities</span>
                                    </a>
                                    <a
                                        href="{{ route('lecturer.missions.edit', $m->mission_id) }}"
                                        class="button secondary"
                                        style="padding: 7px 12px; font-size: 0.8rem; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"
                                        title="Edit mission details"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('lecturer.missions.destroy', $m->mission_id) }}"
                                        onsubmit="return confirm('Are you sure you want to delete this mission? All linked puzzle activities and student attempts will also be removed.');"
                                        style="display: inline;"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="button danger"
                                            style="padding: 7px 12px; font-size: 0.8rem; border-radius: 10px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;"
                                            title="Delete mission"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="text-align: center; padding: 48px 24px; color: rgba(255, 255, 255, 0.5);">
            <div style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.4;">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <p style="font-size: 1.05rem; font-weight: 600; color: #FFFFFF; margin-bottom: 6px;">No missions configured for this topic yet</p>
            <p style="font-size: 0.85rem; max-width: 440px; margin: 0 auto 20px;">
                Create a gamified learning mission to attach interactive puzzle challenges (identification, matching, sequencing, classification, drag-and-drop, and scenario decisions).
            </p>
            @if ($selectedTopic)
                <a
                    href="{{ route('lecturer.missions.create', ['topic_id' => $selectedTopic->topic_id]) }}"
                    class="button"
                    style="padding: 10px 20px; font-size: 0.88rem; border-radius: 12px; text-decoration: none;"
                >
                    <i class="fa-solid fa-plus"></i> Create First Mission
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
