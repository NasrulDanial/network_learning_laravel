@extends('layouts.app')

@section('content')
<div class="quiz-stepper-container" id="quizStepper"
     data-check-url="{{ route('student.quiz.check', $quiz->quiz_id) }}"
     data-abandon-url="{{ route('student.quiz.abandon', $quiz->quiz_id) }}"
     data-lives="{{ $lives }}"
     data-lives-left="{{ $livesLeft }}"
     data-terminated="{{ $terminated ? '1' : '0' }}"
     data-checked='@json((object) $checked)'>
    <!-- Top Breadcrumb & Metadata -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <a class="button secondary" href="{{ route('student.quiz') }}" data-quiz-exit style="font-size: 0.82rem; padding: 8px 16px;">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Exit to Challenge Hub</span>
        </a>

        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="sheet-badge" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.35);">
                <i class="fa-solid fa-bolt" style="color: #fbbf24; margin-right: 4px;"></i>
                {{ $claimableXp > 0 ? '+' . $claimableXp . ' Network XP' : 'XP already claimed' }}
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

    <!-- Quiz HUD: answers are checked one by one and come back as ACK (correct) or RST (wrong) -->
    <div class="quiz-hud" id="quizHud" hidden>
        <span class="quiz-hud-item" title="Correct answers in a row"><i class="fa-solid fa-fire" aria-hidden="true"></i> <span id="quizStreak">x0</span></span>
        <span class="quiz-hud-item quiz-hud-health" id="quizHealth"
              title="Lives: each wrong answer costs one; losing the last ends the attempt"
              aria-label="Lives {{ $livesLeft }} of {{ $lives }}">
            @for ($h = 1; $h <= $lives; $h++)<i class="fa-solid fa-heart {{ $h > $livesLeft ? 'is-lost' : '' }}" aria-hidden="true"></i>@endfor
        </span>
        <span class="quiz-hud-item"><strong id="quizAckCount">0</strong>&nbsp;ACK / <span id="quizAnsweredCount">0</span>&nbsp;sent</span>
        <span class="quiz-hud-server" id="quizServer" title="Quiz server"><i class="fa-solid fa-server" aria-hidden="true"></i></span>
    </div>
    <div class="visually-hidden" id="quizLive" aria-live="polite" aria-atomic="true"></div>

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

                    <h2 class="question-title" tabindex="-1">
                        {{ $question->question_text }}
                    </h2>

                    <!-- Option order is shuffled per attempt; the badge shows the position, data-option the real key -->
                    <div class="options-container" style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($optionOrder[$question->question_id] ?? ['A', 'B', 'C', 'D'] as $position => $optionKey)
                            @php
                                $prop = 'option_' . strtolower($optionKey);
                                $label = chr(65 + $position);
                            @endphp

                            <label class="gamified-option" data-option="{{ $optionKey }}" data-label="{{ $label }}">
                                <input
                                    type="radio"
                                    name="answers[{{ $question->question_id }}]"
                                    value="{{ $optionKey }}"
                                    required
                                    style="position: absolute; opacity: 0; pointer-events: none;"
                                >
                                <span class="option-key-badge">{{ $label }}</span>
                                <span class="option-text-label">{{ $question->$prop }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="quiz-explain" hidden>
                        <div class="quiz-explain-head"></div>
                        <p class="quiz-explain-text"></p>
                        <button type="button" class="button quiz-continue">
                            <span>{{ $loop->last ? 'Finish' : 'Continue' }}</span>
                            <i class="fa-solid {{ $loop->last ? 'fa-flag-checkered' : 'fa-chevron-right' }}" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Submission: with JavaScript each question's Continue button moves on and Finish submits.
             Without it this plain button submits the radios. -->
        <div class="stepper-nav-bar">
            <div style="font-size: 0.75rem; color: var(--nl-text-tertiary); display: flex; align-items: center; gap: 6px;">
                <i class="fa-regular fa-keyboard" aria-hidden="true"></i>
                <span id="quizKeyHint">Keyboard: [A, B, C, D] to answer, [Enter] to continue</span>
            </div>

            <div class="quiz-summary" id="quizSummary" hidden></div>

            <button
                type="submit"
                id="stepperSubmitBtn"
                class="button"
                style="padding: 10px 28px; background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);"
            >
                <i class="fa-solid fa-flag-checkered" aria-hidden="true"></i>
                <span>Finish</span>
            </button>
        </div>
    </form>

    {{-- Out of lives --}}
    <dialog class="quiz-dialog quiz-dialog--down" id="quizLinkDown" aria-labelledby="linkDownTitle">
        <div class="quiz-dialog-icon" aria-hidden="true"><i class="fa-solid fa-plug-circle-xmark"></i></div>
        <h2 id="linkDownTitle">LINK DOWN</h2>
        <p>Passing is no longer possible.</p>
        <p class="quiz-dialog-stats" id="linkDownStats"></p>
        <div class="quiz-dialog-actions">
            <button type="button" class="button" id="linkDownResult">View result</button>
        </div>
    </dialog>

    {{-- Leaving mid-attempt --}}
    <dialog class="quiz-dialog" id="quizLeaveDialog" aria-labelledby="leaveTitle">
        <h2 id="leaveTitle">Leave the assessment?</h2>
        <p>Your answers will be lost and this attempt will be recorded as a failed attempt. You'll start again from question 1.</p>
        <div class="quiz-dialog-actions">
            <button type="button" class="button secondary" id="leaveStay">Stay</button>
            <button type="button" class="button danger" id="leaveConfirm">Leave assessment</button>
        </div>
    </dialog>
</div>
@endsection
