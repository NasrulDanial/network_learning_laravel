@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <p class="eyebrow">Curriculum Path</p>
                <h1>Networking Adventure Journey</h1>
                <p style="margin-bottom: 0;">
                    Progress through structured networking milestones. Each stage contains
                    interactive puzzle missions and evaluations. Complete stages sequentially
                    to unlock advanced operations.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('student.quiz') }}" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fa-solid fa-clipboard-question"></i> Topic Quizzes
                </a>
                <a href="{{ route('student.progress') }}" class="button" style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fa-solid fa-chart-line"></i> My Progress
                </a>
            </div>
        </div>
    </section>

    <div class="learning-path">
        @if ($topics->count() > 0)
            @php
                $previousCompleted = true;
                $topicNumber = 1;
            @endphp

            @foreach ($topics as $topic)
                @php
                    $isFirst = (int) $topic->topic_order === 1;
                    $isUnlocked = $isFirst || $previousCompleted;

                    $learningStatus = $topic->learning_status ?? 'Not Started';
                    $quizStatus = $topic->quiz_status ?? 'Not Attempted';
                    $progressPercentage = $topic->progress_percentage ?? 0;
                    $missionsCount = (int) ($topic->missions_count ?? 0);
                    $completedMissions = (int) ($topic->completed_missions ?? 0);

                    $currentCompleted = $learningStatus === 'Completed';

                    if ($currentCompleted) {
                        $stageClass = 'stage-card stage-unlocked stage-completed';
                        $statusClass = 'status-badge status-completed';
                        $stageIcon = '<i class="fa-solid fa-circle-check"></i>';
                    } elseif ($isUnlocked) {
                        $stageClass = 'stage-card stage-unlocked';
                        $statusClass = $learningStatus === 'In Progress'
                            ? 'status-badge status-progress'
                            : 'status-badge status-not-started';
                        $stageIcon = $topicNumber;
                    } else {
                        $stageClass = 'stage-card stage-locked';
                        $statusClass = 'status-badge status-locked';
                        $stageIcon = '<i class="fa-solid fa-lock"></i>';
                    }
                @endphp

                <article class="{{ $stageClass }}">
                    <div class="stage-marker" aria-hidden="true">
                        {!! $stageIcon !!}
                    </div>

                    <div class="stage-meta">
                        <span class="eyebrow">
                            Stage {{ $topicNumber }}
                        </span>

                        <span class="{{ $statusClass }}">
                            @if (! $isUnlocked)
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                Locked
                            @else
                                {{ $learningStatus }}
                            @endif
                        </span>
                    </div>

                    <h2>{{ $topic->title }}</h2>

                    <p>
                        {{ $topic->description }}
                    </p>

                    <div class="progress-track" style="margin: 1rem 0;">
                        <div
                            class="progress-fill"
                            style="width: {{ min(100, max(0, (float) $progressPercentage)) }}%;
                                {{ $currentCompleted ? 'background: linear-gradient(90deg, var(--success), #34d399);' : '' }}"
                        ></div>
                    </div>

                    <div class="challenge-info-grid">
                        <div class="challenge-info">
                            <span>Stage Status</span>
                            <strong>{{ $learningStatus }}</strong>
                        </div>

                        <div class="challenge-info">
                            <span>Missions (PBL)</span>
                            <strong style="color: var(--cyan);">
                                @if ($missionsCount > 0)
                                    {{ min($completedMissions, $missionsCount) }}/{{ $missionsCount }} completed
                                @else
                                    No missions yet
                                @endif
                            </strong>
                        </div>
                    </div>

                    <div class="stage-actions">
                        @if ($isUnlocked)
                            <a
                                class="button"
                                href="{{ route('student.topic', $topic->topic_id) }}"
                            >
                                <i
                                    class="fa-solid {{ $currentCompleted ? 'fa-eye' : 'fa-play' }}"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    {{ $currentCompleted ? 'Review Stage' : ($learningStatus === 'In Progress' ? 'Continue Stage' : 'Begin Stage') }}
                                </span>
                            </a>
                        @else
                            <p style="margin-bottom: 0; font-style: italic; font-size: 0.9rem;">
                                <i class="fa-solid fa-lock" style="margin-right: 4px;"></i>
                                Complete Stage {{ $topicNumber - 1 }} to unlock this topic.
                            </p>
                        @endif
                    </div>
                </article>

                @php
                    $previousCompleted = $learningStatus === 'Completed';
                    $topicNumber++;
                @endphp
            @endforeach
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-book-open"></i>
                </div>

                <h2>No learning topics available</h2>
                <p>
                    Your lecturer has not added any learning topics yet.
                </p>
            </section>
        @endif
    </div>
@endsection
