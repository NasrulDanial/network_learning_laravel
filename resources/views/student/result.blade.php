@extends('layouts.app')

@section('content')
    <section class="result-panel {{ $isPass ? 'result-success' : 'result-fail' }}">
        <div class="result-icon achievement-icon" aria-hidden="true">
            @if ($isPass)
                <i class="fa-solid fa-trophy"></i>
            @else
                <i class="fa-solid fa-rotate-right"></i>
            @endif
        </div>

        <p class="eyebrow">
            {{ $isPass ? 'Challenge Cleared' : 'Challenge Incomplete' }}
        </p>

        <h1 style="margin-bottom: 0.5rem;">
            {{ $isPass ? 'Excellent Work!' : 'Review and Try Again' }}
        </h1>

        <h2 style="font-weight: 500; font-size: 1.35rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
            {{ $quiz->title }}
        </h2>

        <div class="result-score">
            {{ number_format((float) $result->percentage, 0) }}%
        </div>

        <p style="margin-bottom: 2rem;">
            @if ($isPass)
                You passed the challenge, earned XP, and unlocked the next networking stage.
            @else
                You earned participation XP. Review the learning material before attempting the challenge again.
            @endif
        </p>

        <div class="result-details" style="margin-bottom: 2rem;">
            <div class="result-detail">
                <span>Correct Answers</span>
                <strong>
                    {{ (int) $result->score }}/{{ (int) $result->total_questions }}
                </strong>
            </div>

            <div class="result-detail">
                <span>Status</span>
                <strong>
                    {{ $result->status }}
                </strong>
            </div>

            <div class="result-detail">
                <span>XP Earned</span>
                <strong>
                    +{{ (int) $result->points_earned }} XP
                </strong>
            </div>

            <div class="result-detail">
                <span>Date Taken</span>
                <strong>
                    {{ $result->date_taken }}
                </strong>
            </div>
        </div>

        <div class="actions" style="justify-content: center;">
            @if ($isPass)
                <a class="button" href="{{ route('student.learning') }}">
                    <i class="fa-solid fa-forward-step" aria-hidden="true"></i>
                    Continue Learning Journey
                </a>
            @else
                <a class="button" href="{{ route('student.topic', $quiz->topic_id) }}">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    Review Learning Material
                </a>

                <a class="button secondary" href="{{ route('student.quiz.take', $quiz->quiz_id) }}">
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    Retry Challenge
                </a>
            @endif

            <a class="button secondary" href="{{ route('student.quiz') }}">
                <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i>
                Back to Quiz Hub
            </a>
        </div>
    </section>
@endsection