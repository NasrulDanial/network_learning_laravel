@extends('layouts.app')

@section('content')
<div class="quiz-stepper-container" id="quizStepper">
    <!-- Top Breadcrumb & Metadata -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <a class="button secondary" href="{{ route('student.quiz') }}" style="font-size: 0.82rem; padding: 8px 16px;">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Exit to Challenge Hub</span>
        </a>

        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="sheet-badge" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.35);">
                <i class="fa-solid fa-bolt" style="color: #fbbf24; margin-right: 4px;"></i> +100 Network XP
            </span>
            <span class="sheet-badge">
                PASSING: {{ (int) $quiz->passing_mark }}%
            </span>
        </div>
    </div>

    <!-- Stepper Node Header & Progress Gauge -->
    <div class="stepper-header">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 4px;">Incident Evaluation</span>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #FFFFFF;">
                {{ $quiz->title }}
            </h2>
            <span style="font-size: 0.82rem; color: var(--nl-text-tertiary);">
                Stage Context: <strong style="color: var(--nl-cyan);">{{ $topic->title }}</strong>
            </span>
        </div>

        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
            <div class="stepper-nodes">
                @foreach ($questions as $idx => $q)
                    <div
                        class="stepper-node-dot {{ $idx === 0 ? 'current' : '' }}"
                        data-step="{{ $idx }}"
                        title="Question {{ $idx + 1 }}"
                    >
                        {{ $idx + 1 }}
                    </div>
                @endforeach
            </div>
            <div style="font-size: 0.75rem; color: var(--nl-text-tertiary); font-weight: 600;">
                Question <span id="stepperCurrentNum" style="color: var(--nl-cyan);">1</span> of {{ $questions->count() }}
            </div>
        </div>
    </div>

    <!-- Integrity / Convergence Progress Bar -->
    <div style="height: 4px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; margin-bottom: 24px; overflow: hidden;">
        <div
            id="stepperIntegrityBar"
            style="width: {{ round((1 / max(1, $questions->count())) * 100) }}%; height: 100%; background: var(--nl-gradient-accent); border-radius: 99px; transition: width 0.4s var(--nl-spring);"
        ></div>
    </div>

    <!-- Form Container -->
    <form id="quizChallengeForm" method="POST" action="{{ route('student.quiz.submit') }}">
        @csrf
        <input type="hidden" name="quiz_id" value="{{ $quiz->quiz_id }}">

        @foreach ($questions as $index => $question)
            <div class="quiz-slide {{ $index === 0 ? 'active' : '' }}" data-question-index="{{ $index }}">
                <div class="question-card-gamified">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <span class="sheet-badge" style="font-size: 0.75rem;">
                            PACKET INSPECTION #{{ $index + 1 }}
                        </span>
                        <span style="font-size: 0.78rem; color: var(--nl-text-tertiary);">
                            Single Answer Protocol
                        </span>
                    </div>

                    <h2 class="question-title">
                        {{ $question->question_text }}
                    </h2>

                    <!-- Glowing Squircle Option Cards -->
                    <div class="options-container" style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach (['A', 'B', 'C', 'D'] as $optionKey)
                            @php
                                $prop = 'option_' . strtolower($optionKey);
                            @endphp

                            <label class="gamified-option" data-option="{{ $optionKey }}">
                                <input
                                    type="radio"
                                    name="answers[{{ $question->question_id }}]"
                                    value="{{ $optionKey }}"
                                    required
                                    style="position: absolute; opacity: 0; pointer-events: none;"
                                >
                                <span class="option-key-badge">{{ $optionKey }}</span>
                                <span class="option-text-label">{{ $question->$prop }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Navigation & Submission Controls -->
        <div class="stepper-nav-bar">
            <button
                type="button"
                id="stepperPrevBtn"
                class="button secondary"
                style="display: none; padding: 10px 20px;"
            >
                <i class="fa-solid fa-chevron-left"></i>
                <span>Previous Protocol</span>
            </button>

            <div style="font-size: 0.75rem; color: var(--nl-text-tertiary); display: flex; align-items: center; gap: 6px;">
                <i class="fa-regular fa-keyboard"></i>
                <span>Keyboard: [A, B, C, D] to select, [←, →] to navigate</span>
            </div>

            <div>
                <button
                    type="button"
                    id="stepperNextBtn"
                    class="button"
                    style="padding: 10px 24px;"
                >
                    <span>Next Protocol</span>
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <button
                    type="submit"
                    id="stepperSubmitBtn"
                    class="button"
                    style="display: none; padding: 10px 28px; background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);"
                >
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Transmit Answers</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
