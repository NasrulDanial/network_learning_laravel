@extends('layouts.app')

@section('content')
    <a class="breadcrumb-link" href="{{ route('student.quiz') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Quiz Hub
    </a>

    <section class="page-banner">
        <p class="eyebrow">Quiz Challenge</p>

        <h1>{{ $quiz->title }}</h1>

        <p>
            Topic:
            <strong style="color: var(--cyan);">
                {{ $topic->title }}
            </strong>
        </p>
    </section>

    <section class="challenge-info-grid" style="margin-bottom: 2rem;">
        <div class="challenge-info">
            <span>Total Questions</span>
            <strong>{{ $questions->count() }}</strong>
        </div>

        <div class="challenge-info">
            <span>Passing Mark</span>
            <strong>{{ (int) $quiz->passing_mark }}%</strong>
        </div>

        <div class="challenge-info">
            <span>XP Reward</span>
            <strong>100 XP</strong>
        </div>
    </section>

    <form method="POST" action="{{ route('student.quiz.submit') }}">
        @csrf

        <input
            type="hidden"
            name="quiz_id"
            value="{{ $quiz->quiz_id }}"
        >

        @foreach ($questions as $index => $question)
            <article class="question-card">
                <div class="stage-meta" style="margin-bottom: 1rem;">
                    <span class="eyebrow">
                        Question {{ $index + 1 }}
                    </span>
                </div>

                <h2 style="font-size: 1.15rem;">
                    {{ $question->question_text }}
                </h2>

                <div class="answer-list">
                    @foreach (['A', 'B', 'C', 'D'] as $option)
                        @php
                            $field = 'option_' . strtolower($option);
                        @endphp

                        <label class="answer-option">
                            <input
                                type="radio"
                                name="answers[{{ $question->question_id }}]"
                                value="{{ $option }}"
                                required
                            >

                            <span class="answer-icon" aria-hidden="true">
                                <i class="fa-regular fa-circle"></i>
                            </span>

                            <span class="answer-text">
                                <strong>{{ $option }}.</strong>
                                {{ $question->$field }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </article>
        @endforeach

        <section class="management-card" style="margin-top: 2rem;">
            <p class="eyebrow">Submit Challenge</p>

            <h2>Ready to submit your answers?</h2>

            <p>
                Make sure every question has been answered before submitting.
                Your result and XP will be calculated after submission.
            </p>

            <div class="actions">
                <button type="submit">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Submit Challenge
                </button>

                <a class="button secondary" href="{{ route('student.quiz') }}">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Cancel
                </a>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.answer-option input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const groupName = radio.name;

            document
                .querySelectorAll(`input[name="${groupName}"]`)
                .forEach((item) => {
                    const option = item.closest('.answer-option');
                    const icon = option.querySelector('.answer-icon i');

                    option.classList.remove('selected');
                    icon.className = 'fa-regular fa-circle';
                });

            const selectedOption = radio.closest('.answer-option');
            const selectedIcon = selectedOption.querySelector('.answer-icon i');

            selectedOption.classList.add('selected');
            selectedIcon.className = 'fa-solid fa-circle-check';
        });
    });
});
</script>
@endsection