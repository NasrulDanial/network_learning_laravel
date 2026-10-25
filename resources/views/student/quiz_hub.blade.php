@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <p class="eyebrow">Challenge Centre</p>
        <h1>Quiz Challenge Hub</h1>
        <p>
            Select an unlocked networking challenge, test your knowledge,
            and earn XP by reaching the required passing mark.
        </p>
    </section>

    @if ($errors->any())
        <div class="message error" style="margin-bottom: 1.5rem;">
            @foreach ($errors->all() as $error)
                <p style="margin-bottom: 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section>
        <div class="dashboard-grid">
            @if ($quizzes->count() > 0)
                @php
                    $previousCompleted = true;
                @endphp

                @foreach ($quizzes as $quizItem)
                    @php
                        $isFirst = (int) $quizItem->topic_order === 1;
                        $isUnlocked = $isFirst || $previousCompleted;
                        $learningStatus = $quizItem->learning_status ?? 'Not Started';
                        $quizStatus = $quizItem->quiz_status ?? 'Not Attempted';
                        $isPassed = $quizStatus === 'Passed';

                        if ($isPassed) {
                            $cardClass = 'card';
                            $statusClass = 'status-badge status-pass';
                            $icon = 'fa-trophy';
                        } elseif ($isUnlocked) {
                            $cardClass = 'card';
                            $statusClass = 'status-badge status-progress';
                            $icon = 'fa-bullseye';
                        } else {
                            $cardClass = 'card stage-locked';
                            $statusClass = 'status-badge status-locked';
                            $icon = 'fa-lock';
                        }
                    @endphp

                    <article class="{{ $cardClass }}">
                        <span class="card-icon" aria-hidden="true">
                            <i class="fa-solid {{ $icon }}"></i>
                        </span>

                        <div class="stage-meta">
                            <span class="eyebrow">
                                Stage {{ (int) $quizItem->topic_order }}
                            </span>

                            <span class="{{ $statusClass }}">
                                @if (! $isUnlocked)
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    Locked
                                @else
                                    {{ $quizStatus }}
                                @endif
                            </span>
                        </div>

                        <h2 style="margin-top: 0.5rem; margin-bottom: 0.25rem;">
                            {{ $quizItem->quiz_title }}
                        </h2>

                        <p style="font-weight: 600; font-size: 0.95rem; color: var(--cyan); margin-bottom: 0.5rem;">
                            {{ $quizItem->topic_title }}
                        </p>

                        <p style="font-size: 0.9rem; flex: 1;">
                            {{ $quizItem->topic_description }}
                        </p>

                        <div class="challenge-info-grid" style="margin-bottom: 1.5rem;">
                            <div class="challenge-info">
                                <span>Questions</span>
                                <strong>{{ (int) $quizItem->total_questions }}</strong>
                            </div>

                            <div class="challenge-info">
                                <span>Pass Mark</span>
                                <strong>{{ (int) $quizItem->passing_mark }}%</strong>
                            </div>
                        </div>

                        @if ($isUnlocked)
                            <div class="stage-actions">
                                <a
                                    class="button"
                                    href="{{ route('student.quiz.take', $quizItem->quiz_id) }}"
                                >
                                    <i
                                        class="fa-solid {{ $isPassed ? 'fa-rotate-right' : 'fa-play' }}"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        {{ $isPassed ? 'Retry Challenge' : 'Start Challenge' }}
                                    </span>
                                </a>
                            </div>
                        @else
                            <p style="margin-bottom: 0; font-style: italic; font-size: 0.9rem;">
                                Complete the previous stage to unlock this quiz.
                            </p>
                        @endif
                    </article>

                    @php
                        $previousCompleted = $learningStatus === 'Completed';
                    @endphp
                @endforeach
            @else
                <section class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-state-icon" aria-hidden="true">
                        <i class="fa-solid fa-puzzle-piece"></i>
                    </div>

                    <h2>No quiz challenges available</h2>
                    <p>Your lecturer has not created any quizzes yet.</p>
                </section>
            @endif
        </div>
    </section>
@endsection