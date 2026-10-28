{{--
    Topology mission board: each mission is a device on one network path that ends at the
    topic quiz. Cleared links carry traffic; locked devices name the mission that unlocks them.
    Expects $missions (with student_* attributes), $quiz and $runMode.
--}}
@php
    $deviceIcons = ['fa-laptop', 'fa-network-wired', 'fa-shield-halved', 'fa-tower-broadcast', 'fa-server'];
    $allCleared = $missions->every(fn ($m) => $m->student_is_completed);
    $clearedCount = $missions->where('student_is_completed', true)->count();
@endphp

<section class="topo-board" aria-labelledby="topo-heading">
    <div class="topo-head">
        <h3 id="topo-heading"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Network path</h3>
        <span class="topo-count">{{ $clearedCount }}/{{ $missions->count() }} {{ Str::plural('segment', $missions->count()) }} up</span>
    </div>

    <ol class="topo-path">
        @foreach ($missions as $i => $mission)
            @php
                $state = $mission->student_is_completed ? 'cleared' : ($mission->student_is_unlocked ? 'active' : 'locked');
                $icon = $deviceIcons[$i % count($deviceIcons)];
                $previous = $i > 0 ? $missions[$i - 1] : null;
                $href = $runMode
                    ? route('student.mission.run', $mission->mission_id) . ($mission->student_is_completed ? '?restart=1' : '')
                    : '#mission-' . $mission->mission_id;
                $stateLabel = ['cleared' => 'cleared', 'active' => 'ready', 'locked' => 'locked'][$state];
                // Titles often repeat their number ("Mission 2: ..."); the board already shows order
                $previousShort = $previous ? (preg_replace('/^mission\s*\d+\s*[:\-–—]\s*/iu', '', $previous->title) ?: $previous->title) : '';
                $shortTitle = preg_replace('/^mission\s*\d+\s*[:\-–—]\s*/iu', '', $mission->title) ?: $mission->title;
            @endphp

            @if ($i > 0)
                <li class="topo-link is-{{ $previous->student_is_completed ? 'up' : 'down' }}" aria-hidden="true">
                    <svg viewBox="0 0 100 10" preserveAspectRatio="none" focusable="false">
                        <line x1="0" y1="5" x2="100" y2="5"/>
                    </svg>
                </li>
            @endif

            <li class="topo-item">
                @if ($state === 'locked')
                    <span class="topo-node is-locked" tabindex="0" aria-describedby="topo-tip-{{ $mission->mission_id }}">
                        <span class="topo-device"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                        <span class="topo-name" title="{{ $mission->title }}">{{ $shortTitle }}</span>
                        <span class="visually-hidden">Mission {{ $i + 1 }}, {{ $stateLabel }}</span>
                        <span class="topo-tip" role="tooltip" id="topo-tip-{{ $mission->mission_id }}">
                            Complete “{{ $previousShort }}” to unlock
                        </span>
                    </span>
                @else
                    <a class="topo-node is-{{ $state }}" href="{{ $href }}"
                       aria-label="Mission {{ $i + 1 }}: {{ $shortTitle }}, {{ $stateLabel }}{{ $state === 'cleared' ? ', ' . $mission->student_stars . ' of 3 stars' : '' }}">
                        <span class="topo-device"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span>
                        <span class="topo-name" title="{{ $mission->title }}">{{ $shortTitle }}</span>
                        @if ($state === 'cleared')
                            <span class="topo-stars" aria-hidden="true">
                                @for ($s = 1; $s <= 3; $s++)<i class="fa-solid fa-star {{ $s <= $mission->student_stars ? 'is-lit' : '' }}"></i>@endfor
                            </span>
                        @else
                            <span class="topo-state">{{ $mission->student_done_count > 0 ? 'In progress' : 'Ready' }}</span>
                        @endif
                    </a>
                @endif
            </li>
        @endforeach

        @if ($quiz)
            <li class="topo-link is-{{ $allCleared ? 'up' : 'down' }}" aria-hidden="true">
                <svg viewBox="0 0 100 10" preserveAspectRatio="none" focusable="false"><line x1="0" y1="5" x2="100" y2="5"/></svg>
            </li>
            <li class="topo-item">
                <a class="topo-node is-core {{ $allCleared ? 'is-active' : '' }}" href="{{ route('student.quiz.take', $quiz->quiz_id) }}"
                   aria-label="Core: {{ $quiz->title }} (assessment quiz)">
                    <span class="topo-device"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i></span>
                    <span class="topo-name">Quiz</span>
                    <span class="topo-state">Core</span>
                </a>
            </li>
        @endif
    </ol>

    @if ($allCleared)
        <div class="topo-banner" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span><strong>End-to-end connectivity established.</strong> Every mission on this path is cleared.</span>
            @if ($quiz)
                <a class="button topo-banner-cta" href="{{ route('student.quiz.take', $quiz->quiz_id) }}">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Take the quiz
                </a>
            @endif
        </div>
    @endif
</section>
