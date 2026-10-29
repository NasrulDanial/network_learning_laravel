@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <p class="eyebrow">Topic Assessments</p>
                <h1>Topic Quizzes</h1>
                <p style="margin-bottom: 0;">
                    Each topic ends with a short multiple-choice quiz. Passing it completes
                    the topic and unlocks the next one in your learning journey.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('student.learning') }}" class="button" style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fa-solid fa-rocket"></i> Curriculum Path
                </a>
                <a href="{{ route('student.progress') }}" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fa-solid fa-chart-line"></i> Performance
                </a>
            </div>
        </div>
    </section>

    {{-- How quizzes count --}}
    <div class="management-card" style="margin-bottom: 1.5rem; padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-color: rgba(34, 211, 238, 0.2);">
        <div style="width: 36px; height: 36px; border-radius: var(--radius-sm); background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.25); color: var(--cyan); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="fa-solid fa-info"></i>
        </div>
        <p style="margin: 0; font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5;">
            <strong style="color: var(--text-main);">How quizzes count:</strong>
            you can retake a quiz as often as you like, and your topic stays completed once you pass.
            XP is awarded once per quiz: 100 XP for your first pass (or 20 XP if your first attempt
            does not pass). Interactive puzzle missions are in your
            <a href="{{ route('student.learning') }}">Learning Journey</a>.
        </p>
    </div>

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
                @foreach ($quizzes as $quizItem)
                    @php
                        $isUnlocked = (bool) $quizItem->is_unlocked;
                        $learningStatus = $quizItem->learning_status ?? 'Not Started';
                        $quizStatus = $quizItem->quiz_status ?? 'Not Attempted';
                        $isPassed = $quizStatus === 'Passed';
                        $isFailed = $quizStatus === 'Failed';

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
                                @elseif ($isPassed)
                                    <i class="fa-solid fa-check-circle"></i> Passed
                                @elseif ($isFailed)
                                    <i class="fa-solid fa-times-circle"></i> Retake Needed
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
                                        {{ $isPassed || $isFailed ? 'Retry assessment' : 'Start assessment' }}
                                    </span>
                                </a>
                            </div>
                        @elseif ($quizItem->lock_reason === 'missions')
                            <div class="hub-mission-lock">
                                <p style="margin: 0 0 8px; font-size: 0.9rem;">
                                    <i class="fa-solid fa-lock" style="margin-right: 4px;" aria-hidden="true"></i>
                                    Clear missions first ({{ $quizItem->missions_done }}/{{ $quizItem->missions_total }})
                                </p>
                                <div class="hub-mission-bar" role="progressbar" aria-label="Missions cleared"
                                     aria-valuemin="0" aria-valuemax="{{ $quizItem->missions_total }}" aria-valuenow="{{ $quizItem->missions_done }}">
                                    <span style="width: {{ $quizItem->missions_total ? round($quizItem->missions_done / $quizItem->missions_total * 100) : 0 }}%"></span>
                                </div>
                                <a class="button secondary" href="{{ route('student.topic', ['topic' => $quizItem->topic_id, 'tab' => 'missions']) }}" style="margin-top: 12px;">
                                    <i class="fa-solid fa-route" aria-hidden="true"></i> <span>Go to missions</span>
                                </a>
                            </div>
                        @else
                            <p style="margin-bottom: 0; font-style: italic; font-size: 0.9rem;">
                                <i class="fa-solid fa-lock" style="margin-right: 4px;"></i>
                                Complete the previous stage to unlock this quiz.
                            </p>
                        @endif
                    </article>
                @endforeach
            @else
                <section class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-state-icon" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>

                    <h2>No Practice Quizzes Available</h2>
                    <p>
                        Your lecturer has not configured any quiz evaluations yet.
                    </p>
                </section>
            @endif
        </div>
    </section>
@endsection
