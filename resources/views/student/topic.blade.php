@extends('layouts.app')

@section('content')
    <a class="breadcrumb-link" href="{{ route('student.learning') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Learning Journey
    </a>

    <section class="page-banner">
        <p class="eyebrow">Learning Mission</p>

        <h1>{{ $topic->title }}</h1>

        <p>
            {{ $topic->description }}
        </p>
    </section>

    <section class="management-card" style="margin-bottom: 2rem;">
        <div class="stage-meta" style="margin-bottom: 1rem;">
            <span class="eyebrow">
                Stage {{ $topic->topic_order }}
            </span>

            @if ($quiz)
                <span class="status-badge status-progress">
                    <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
                    Pass Mark: {{ (int) $quiz->passing_mark }}%
                </span>
            @else
                <span class="status-badge status-locked">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    No Quiz Available
                </span>
            @endif
        </div>

        <div class="topic-content">
            {!! nl2br(e($topic->content)) !!}
        </div>
    </section>

    <section class="actions">
        @if ($quiz)
            <a
                class="button"
                href="{{ route('student.quiz.take', $quiz->quiz_id) }}"
            >
                <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i>
                Start Quiz Challenge
            </a>
        @else
            <button class="button secondary" type="button" disabled>
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                Quiz Not Available
            </button>
        @endif

        <a class="button secondary" href="{{ route('student.learning') }}">
            <i class="fa-solid fa-route" aria-hidden="true"></i>
            Return to Learning Journey
        </a>
    </section>
@endsection