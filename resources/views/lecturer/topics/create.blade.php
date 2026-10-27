@extends('layouts.app')

@section('content')
<div style="margin-bottom: 20px;">
    <a class="breadcrumb-link" href="{{ route('lecturer.topics.index') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        <span>Back to Manage Topics</span>
    </a>
</div>

<section class="page-banner" style="margin-bottom: 24px;">
    <p class="eyebrow">Curriculum Design</p>
    <h1>Add New Learning Stage</h1>
    <p>Configure curriculum objectives, learning theory, difficulty rating, and optional stage cover imagery.</p>
</section>

@if ($errors->any())
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; font-size: 0.88rem;">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
    <form method="POST" action="{{ route('lecturer.topics.store') }}" enctype="multipart/form-data">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <!-- Title -->
            <div class="form-row">
                <label for="title">Topic Title <span style="color: #f87171;">*</span></label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    class="macos-input"
                    placeholder="e.g. OSI Reference Model & PDU Encapsulation"
                    value="{{ old('title') }}"
                    required
                >
            </div>

            <!-- Sequence Order -->
            <div class="form-row">
                <label for="topic_order">Stage Sequence Order <span style="color: #f87171;">*</span></label>
                <input
                    id="topic_order"
                    type="number"
                    name="topic_order"
                    class="macos-input"
                    min="1"
                    value="{{ old('topic_order', $nextOrder ?? 1) }}"
                    required
                >
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <!-- Difficulty -->
            <div class="form-row">
                <label for="difficulty">Pedagogical Difficulty</label>
                <select id="difficulty" name="difficulty" class="macos-select">
                    <option value="beginner" {{ old('difficulty') === 'beginner' ? 'selected' : '' }}>Beginner (Foundational)</option>
                    <option value="intermediate" {{ old('difficulty') === 'intermediate' ? 'selected' : '' }}>Intermediate (Core Concepts)</option>
                    <option value="advanced" {{ old('difficulty') === 'advanced' ? 'selected' : '' }}>Advanced (Complex Protocols)</option>
                </select>
            </div>

            <!-- Status -->
            <div class="form-row">
                <label for="status">Publication State</label>
                <select id="status" name="status" class="macos-select">
                    <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published (Active to Students)</option>
                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Hidden in Development)</option>
                </select>
            </div>

            <!-- Estimated Learning Time -->
            <div class="form-row">
                <label for="estimated_time_minutes">Estimated Learning Time (Mins)</label>
                <input
                    type="number"
                    id="estimated_time_minutes"
                    name="estimated_time_minutes"
                    class="macos-input"
                    min="5"
                    max="300"
                    step="5"
                    value="{{ old('estimated_time_minutes', 30) }}"
                >
            </div>
        </div>

        <!-- Short Description -->
        <div class="form-row">
            <label for="description">Curriculum Card Summary (Concise)</label>
            <textarea
                id="description"
                name="description"
                rows="2"
                class="macos-input"
                placeholder="Brief 1-2 sentence overview shown on student learning journey cards..."
            >{{ old('description') }}</textarea>
        </div>

        <!-- Learning Objectives / Outcomes (Blueprint B2) -->
        <div class="form-row">
            <label for="objectives">Learning Objectives & Outcomes</label>
            <textarea
                id="objectives"
                name="objectives"
                rows="3"
                class="macos-input"
                placeholder="e.g. 1. Identify the 7 OSI layers in order&#10;2. Distinguish transport vs network layer protocols&#10;3. Map data encapsulation PDUs..."
            >{{ old('objectives') }}</textarea>
            <span class="field-hint">Specify concrete operational competencies students will develop in this curriculum stage.</span>
        </div>

        <!-- Optional Cover Image Upload -->
        <div class="form-row">
            <label for="cover_image">Stage Cover Imagery (Optional)</label>
            <input
                type="file"
                id="cover_image"
                name="cover_image"
                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                class="macos-input"
                onchange="previewCoverImage(this)"
            >
            <span class="field-hint">Upload an informative diagram or topology graphic (PNG, JPG, WEBP, max 2MB). If omitted, an abstract SVG network emblem is deterministically generated.</span>

            <div id="coverImagePreviewContainer" style="display: none; margin-top: 12px;">
                <img id="coverImagePreview" src="" alt="Cover preview" style="max-height: 140px; border-radius: 10px; border: 1px solid var(--nl-glass-border);">
            </div>
        </div>

        <!-- Core Structured Learning Content -->
        <div class="form-row">
            <label for="content">Theoretical Learning Material & Notes <span style="color: #f87171;">*</span></label>
            <textarea
                id="content"
                name="content"
                rows="10"
                class="macos-input"
                style="line-height: 1.55; font-family: inherit;"
                placeholder="Provide structured networking theory, conceptual definitions, architecture diagrams, and review material..."
                required
            >{{ old('content') }}</textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--nl-glass-border);">
            <a class="button secondary" href="{{ route('lecturer.topics.index') }}">
                Cancel
            </a>
            <button type="submit" class="button">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span>Save Stage</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function previewCoverImage(input) {
    const container = document.getElementById('coverImagePreviewContainer');
    const preview = document.getElementById('coverImagePreview');
    if (!input.files || !input.files[0]) {
        container.style.display = 'none';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        container.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endsection
