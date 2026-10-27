@extends('layouts.app')

@section('content')
<section class="page-banner" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <p class="eyebrow">Curriculum Architecture</p>
            <h1>Manage Networking Topics</h1>
            <p>Author curriculum stages, organize learning objectives, and configure pedagogical sequencing.</p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button
                type="button"
                id="btnToggleReorder"
                class="button secondary"
                onclick="toggleReorderMode()"
                style="padding: 9px 16px; font-size: 0.84rem;"
            >
                <i class="fa-solid fa-arrow-down-1-9"></i>
                <span id="reorderBtnLabel">Reorder Stages</span>
            </button>

            <a class="button" href="{{ route('lecturer.topics.create') }}" style="padding: 9px 18px; font-size: 0.84rem;">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Add Curriculum Stage</span>
            </a>
        </div>
    </div>
</section>

@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #4ade80; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.88rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="message error" role="alert">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<!-- Reorder Interactive Drawer / Panel (Blueprint B2: Accessible Alternative to Drag & Drop) -->
<div id="reorderPanel" style="display: none; background: rgba(34, 211, 238, 0.05); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 16px; padding: 20px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                <i class="fa-solid fa-arrows-up-down" style="color: var(--nl-cyan); margin-right: 6px;"></i>
                Reorder Curriculum Sequence
            </h3>
            <span style="font-size: 0.78rem; color: var(--nl-text-secondary);">
                Use Move Up / Move Down to arrange stages. Sequence updates atomically in one transaction without duplicate order keys.
            </span>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="button" class="button secondary" onclick="toggleReorderMode()" style="padding: 6px 14px; font-size: 0.78rem;">
                Cancel
            </button>
            <form id="reorderForm" method="POST" action="{{ route('lecturer.topics.reorder') }}" style="display: inline;">
                @csrf
                <div id="reorderInputsContainer"></div>
                <button type="button" class="button" onclick="submitTopicReorder()" style="padding: 6px 16px; font-size: 0.78rem;">
                    <i class="fa-solid fa-check"></i> Save New Order
                </button>
            </form>
        </div>
    </div>

    <div id="reorderList" style="display: flex; flex-direction: column; gap: 8px;">
        @foreach ($topics as $index => $t)
            <div
                class="reorder-item-row"
                data-topic-id="{{ $t->topic_id }}"
                style="background: var(--nl-glass-card); border: 1px solid var(--nl-glass-border); border-radius: 10px; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px;"
            >
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="stage-order-badge" style="font-weight: 800; color: var(--nl-cyan); font-size: 0.85rem; width: 68px;">
                        Stage {{ $index + 1 }}
                    </span>
                    <strong style="color: #FFFFFF; font-size: 0.9rem;">{{ $t->title }}</strong>
                </div>

                <div style="display: flex; gap: 6px;">
                    <button
                        type="button"
                        class="button secondary"
                        onclick="moveTopicItem(this, -1)"
                        style="padding: 4px 10px; font-size: 0.72rem;"
                        title="Move Stage Up"
                    >
                        <i class="fa-solid fa-arrow-up"></i> Up
                    </button>
                    <button
                        type="button"
                        class="button secondary"
                        onclick="moveTopicItem(this, 1)"
                        style="padding: 4px 10px; font-size: 0.72rem;"
                        title="Move Stage Down"
                    >
                        <i class="fa-solid fa-arrow-down"></i> Down
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Responsive Topic Cards Grid (Blueprint B2) -->
@if ($topics->count() > 0)
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; margin-bottom: 32px;">
        @php
            $topicFallbacks = [
                1 => 'fa-network-wired',
                2 => 'fa-layer-group',
                3 => 'fa-diagram-project',
                4 => 'fa-calculator',
                5 => 'fa-route',
                6 => 'fa-shield-halved',
                7 => 'fa-server',
            ];
            $diffColors = [
                'beginner' => '#4ade80',
                'intermediate' => '#facc15',
                'advanced' => '#f87171',
            ];
        @endphp

        @foreach ($topics as $topic)
            @php
                $isPublished = ($topic->status ?? 'published') === 'published';
                $diff = $topic->difficulty ?? 'beginner';
                $diffColor = $diffColors[$diff] ?? '#4ade80';
                $iconClass = $topicFallbacks[$topic->topic_order % 7 ?: 1] ?? 'fa-diagram-project';
            @endphp
            <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-card); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">

                <!-- Card Header with Fallback Graphic or Cover Image -->
                <div style="height: 120px; position: relative; overflow: hidden; background: linear-gradient(135deg, rgba(13, 33, 56, 0.9) 0%, rgba(7, 20, 38, 0.95) 100%); display: flex; align-items: center; justify-content: center;">
                    @if ($topic->cover_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($topic->cover_image))
                        <img
                            src="{{ asset('storage/' . $topic->cover_image) }}"
                            alt="Cover for {{ $topic->title }}"
                            style="width: 100%; height: 100%; object-fit: cover;"
                        >
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(7, 20, 38, 0.85) 0%, transparent 60%);"></div>
                    @else
                        <!-- Deterministic High-Contrast Abstract Net Graphic -->
                        <div style="font-size: 3rem; color: rgba(34, 211, 238, 0.2); display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid {{ $iconClass }}"></i>
                        </div>
                    @endif

                    <!-- Top Floating Chips -->
                    <div style="position: absolute; top: 12px; left: 14px; display: flex; gap: 6px;">
                        <span style="font-size: 0.72rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; background: rgba(7, 20, 38, 0.85); border: 1px solid rgba(34, 211, 238, 0.35); color: var(--nl-cyan);">
                            Stage {{ $topic->topic_order }}
                        </span>
                        <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 6px; background: rgba(7, 20, 38, 0.85); border: 1px solid rgba(255, 255, 255, 0.1); color: {{ $diffColor }};">
                            {{ ucfirst($diff) }}
                        </span>
                    </div>

                    <div style="position: absolute; top: 12px; right: 14px;">
                        <span class="status-pill {{ $isPublished ? 'pass' : 'fail' }}" style="font-size: 0.68rem;">
                            {{ $isPublished ? 'Published' : 'Draft' }}
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div style="padding: 18px 20px; flex: 1; display: flex; flex-direction: column;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 6px 0; color: #FFFFFF;">
                        {{ $topic->title }}
                    </h3>

                    <p style="font-size: 0.82rem; color: var(--nl-text-secondary); line-height: 1.45; margin: 0 0 16px 0; flex: 1;">
                        {{ Str::limit($topic->description ?? 'No summary provided.', 120) }}
                    </p>

                    <!-- Meta Badges -->
                    <div style="display: flex; gap: 14px; font-size: 0.76rem; color: var(--nl-text-tertiary); padding-top: 12px; border-top: 1px solid var(--nl-glass-border); margin-bottom: 16px;">
                        <span><i class="fa-solid fa-puzzle-piece" style="color: var(--nl-cyan); margin-right: 4px;"></i> {{ $topic->missions_count }} {{ Str::plural('Mission', $topic->missions_count) }}</span>
                        <span><i class="fa-solid fa-clock" style="margin-right: 4px;"></i> ~{{ $topic->estimated_time_minutes ?? 30 }} mins</span>
                    </div>

                    <!-- Action Buttons Toolbar (Blueprint B2: Preview, Edit, Manage Missions, Publish/Unpublish, Delete) -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <a
                            href="{{ route('lecturer.missions.index', ['topic_id' => $topic->topic_id]) }}"
                            class="button"
                            style="padding: 6px 14px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 6px;"
                            title="Manage PBL missions for this stage"
                        >
                            <i class="fa-solid fa-puzzle-piece"></i>
                            <span>Missions ({{ $topic->missions_count }})</span>
                        </a>

                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <a
                                href="{{ route('lecturer.topics.edit', $topic->topic_id) }}"
                                class="button secondary"
                                style="padding: 6px 10px; font-size: 0.78rem;"
                                title="Edit Topic Details"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <!-- Publish / Unpublish Toggle -->
                            <form method="POST" action="{{ route('lecturer.topics.toggle', $topic->topic_id) }}" style="display: inline;">
                                @csrf
                                <button
                                    type="submit"
                                    class="button secondary"
                                    style="padding: 6px 10px; font-size: 0.78rem;"
                                    title="{{ $isPublished ? 'Unpublish (hide from students)' : 'Publish to students' }}"
                                >
                                    <i class="fa-solid {{ $isPublished ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>

                            <!-- Delete with Confirmation -->
                            <form
                                method="POST"
                                action="{{ route('lecturer.topics.destroy', $topic->topic_id) }}"
                                onsubmit="return confirm('Delete topic \'{{ addslashes($topic->title) }}\'? Linked missions and historical evaluation attempts will be safely archived or removed.');"
                                style="display: inline;"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="button secondary"
                                    style="padding: 6px 10px; font-size: 0.78rem; color: #f87171; border-color: rgba(239, 68, 68, 0.3);"
                                    title="Delete Topic"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>
@else
    <div style="text-align: center; padding: 60px 20px; background: var(--nl-glass-card); border-radius: var(--nl-radius-panel); border: 1px solid var(--nl-glass-border);">
        <i class="fa-solid fa-diagram-project" style="font-size: 3rem; color: rgba(255,255,255,0.2); margin-bottom: 12px; display: block;"></i>
        <h3 style="font-size: 1.15rem; color: #FFFFFF; margin-bottom: 6px;">No Curriculum Topics Configured</h3>
        <p style="font-size: 0.88rem; color: var(--nl-text-secondary); margin-bottom: 20px;">
            Create your first networking topic stage to establish the student learning path.
        </p>
        <a class="button" href="{{ route('lecturer.topics.create') }}">
            <i class="fa-solid fa-plus"></i> Add First Topic
        </a>
    </div>
@endif
@endsection

@section('scripts')
<script>
function toggleReorderMode() {
    const panel = document.getElementById('reorderPanel');
    const btnLabel = document.getElementById('reorderBtnLabel');
    if (!panel) return;

    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        if (btnLabel) btnLabel.textContent = 'Close Reorder';
        panel.scrollIntoView({ behavior: 'smooth' });
    } else {
        panel.style.display = 'none';
        if (btnLabel) btnLabel.textContent = 'Reorder Stages';
    }
}

function moveTopicItem(button, direction) {
    const row = button.closest('.reorder-item-row');
    if (!row) return;

    if (direction === -1 && row.previousElementSibling) {
        row.parentNode.insertBefore(row, row.previousElementSibling);
    } else if (direction === 1 && row.nextElementSibling) {
        row.parentNode.insertBefore(row.nextElementSibling, row);
    }

    // Refresh visual badge numbers
    updateReorderBadges();
}

function updateReorderBadges() {
    const rows = document.querySelectorAll('#reorderList .reorder-item-row');
    rows.forEach((r, idx) => {
        const badge = r.querySelector('.stage-order-badge');
        if (badge) badge.textContent = 'Stage ' + (idx + 1);
    });
}

function submitTopicReorder() {
    const container = document.getElementById('reorderInputsContainer');
    const form = document.getElementById('reorderForm');
    const rows = document.querySelectorAll('#reorderList .reorder-item-row');

    if (!container || !form || !rows.length) return;
    container.innerHTML = '';

    rows.forEach((r) => {
        const id = r.getAttribute('data-topic-id');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'topic_ids[]';
        input.value = id;
        container.appendChild(input);
    });

    form.submit();
}
</script>
@endsection
