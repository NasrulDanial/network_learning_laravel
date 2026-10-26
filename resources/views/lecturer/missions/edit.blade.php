@extends('layouts.app')

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <a href="{{ route('lecturer.missions.show', $mission->mission_id) }}" style="font-size: 0.85rem; color: var(--nl-cyan, #22d3ee); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Mission Details
            </a>
            <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Edit Mission: {{ $mission->title }}</h1>
            <p style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.6); margin-top: 4px; margin-bottom: 0;">
                Update mission parameters, pass marks, and XP progression settings.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; font-size: 0.9rem;">
            <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 32px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
        <form method="POST" action="{{ route('lecturer.missions.update', $mission->mission_id) }}">
            @csrf
            @method('PUT')

            <!-- Stage / Topic Selection -->
            <div style="margin-bottom: 22px;">
                <label for="topic_id" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Curriculum Topic / Stage <span style="color: #f87171;">*</span>
                </label>
                <select
                    id="topic_id"
                    name="topic_id"
                    required
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                >
                    @foreach ($topics as $t)
                        <option value="{{ $t->topic_id }}" {{ old('topic_id', $mission->topic_id) == $t->topic_id ? 'selected' : '' }}>
                            Stage {{ $t->topic_order }}: {{ $t->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Title -->
            <div style="margin-bottom: 22px;">
                <label for="title" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Mission Title <span style="color: #f87171;">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $mission->title) }}"
                    required
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                />
            </div>

            <!-- Narrative Objective -->
            <div style="margin-bottom: 22px;">
                <label for="objective" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Narrative Mission Objective / Scenario
                </label>
                <textarea
                    id="objective"
                    name="objective"
                    rows="3"
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem; font-family: inherit; resize: vertical;"
                >{{ old('objective', $mission->objective) }}</textarea>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 22px;">
                <label for="description" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Summary Description
                </label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    value="{{ old('description', $mission->description) }}"
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                />
            </div>

            <!-- Grid: Difficulty, Pass Mark, XP, Order -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 22px;">
                <div>
                    <label for="difficulty" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Difficulty <span style="color: #f87171;">*</span>
                    </label>
                    <select
                        id="difficulty"
                        name="difficulty"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    >
                        <option value="beginner" {{ old('difficulty', $mission->difficulty) === 'beginner' ? 'selected' : '' }}>Beginner</option>
                        <option value="intermediate" {{ old('difficulty', $mission->difficulty) === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="advanced" {{ old('difficulty', $mission->difficulty) === 'advanced' ? 'selected' : '' }}>Advanced</option>
                    </select>
                </div>

                <div>
                    <label for="pass_mark" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Pass Mark (%) <span style="color: #f87171;">*</span>
                    </label>
                    <input
                        type="number"
                        id="pass_mark"
                        name="pass_mark"
                        min="50"
                        max="100"
                        value="{{ old('pass_mark', $mission->pass_mark) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    />
                </div>

                <div>
                    <label for="xp_reward" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Network XP Reward <span style="color: #f87171;">*</span>
                    </label>
                    <input
                        type="number"
                        id="xp_reward"
                        name="xp_reward"
                        min="25"
                        max="1000"
                        step="25"
                        value="{{ old('xp_reward', $mission->xp_reward) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    />
                </div>

                <div>
                    <label for="mission_order" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Sequence Order <span style="color: #f87171;">*</span>
                    </label>
                    <input
                        type="number"
                        id="mission_order"
                        name="mission_order"
                        min="1"
                        value="{{ old('mission_order', $mission->mission_order) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    />
                </div>
            </div>

            <!-- Status -->
            <div style="margin-bottom: 28px;">
                <label for="status" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Publication Status <span style="color: #f87171;">*</span>
                </label>
                <select
                    id="status"
                    name="status"
                    required
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                >
                    <option value="published" {{ old('status', $mission->status) === 'published' ? 'selected' : '' }}>Published (Visible to students)</option>
                    <option value="draft" {{ old('status', $mission->status) === 'draft' ? 'selected' : '' }}>Draft (Hidden from students)</option>
                </select>
            </div>

            <!-- Actions -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <a
                    href="{{ route('lecturer.missions.show', $mission->mission_id) }}"
                    class="button secondary"
                    style="padding: 11px 20px; font-size: 0.9rem; border-radius: 12px; text-decoration: none;"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="button"
                    style="padding: 11px 24px; font-size: 0.9rem; border-radius: 12px; box-shadow: 0 0 15px rgba(34, 211, 238, 0.25);"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
