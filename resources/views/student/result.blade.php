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

            {{-- XP Earned — glowing pill on a pass -------------------------}}
            <div class="result-detail">
                <span>XP Earned</span>
                @if ($isPass)
                    <strong class="xp-counter"
                            style="display:inline-block;padding:0.15rem 0.75rem;border-radius:0.5rem;
                                   background:linear-gradient(135deg,#facc15,#f97316);
                                   color:#1c1917;
                                   transition:box-shadow 0.3s ease;">
                        +{{ (int) $result->points_earned }} XP
                    </strong>
                @else
                    <strong>+{{ (int) $result->points_earned }} XP</strong>
                @endif
            </div>

            <div class="result-detail">
                <span>Date Taken</span>
                <strong>
                    {{ \Carbon\Carbon::parse($result->date_taken)->format('M d, Y h:i A') }}
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
                Back to Topic Quizzes
            </a>
        </div>
    </section>
@endsection

@if ($isPass)
@section('scripts')
<script>
    /**
     * Dependency-free confetti + XP banner on a passing result.
     * Fires 350 ms after page load so the result panel renders first.
     */
    (function () {
        var colours = ['#facc15','#22d3ee','#a78bfa','#34d399','#fb7185','#60a5fa'];

        function spawnConfetti(count) {
            var container = document.createElement('div');
            container.style.cssText = 'position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:9997;';
            document.body.appendChild(container);

            if (!document.getElementById('confetti-style')) {
                var s = document.createElement('style');
                s.id = 'confetti-style';
                s.textContent = '@keyframes confetti-fall{0%{transform:translateY(0) rotate(0deg);opacity:1}80%{opacity:1}100%{transform:translateY(105vh) rotate(var(--spin,360deg));opacity:0}}';
                document.head.appendChild(s);
            }

            for (var i = 0; i < count; i++) {
                var el   = document.createElement('div');
                var size = 8 + Math.random() * 8;
                var col  = colours[Math.floor(Math.random() * colours.length)];
                var spin = Math.random() > 0.5 ? 360 : -360;
                el.style.cssText = 'position:absolute;width:'+size+'px;height:'+(size*0.4)+'px;background:'+col+';border-radius:2px;top:-20px;left:'+(Math.random()*100)+'%;animation:confetti-fall '+(1200+Math.random()*1000)+'ms '+(Math.random()*600)+'ms ease-in forwards;--spin:'+spin+'deg;';
                container.appendChild(el);
            }
            setTimeout(function(){ container.remove(); }, 3200);
        }

        function showXPBanner(xp) {
            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:fixed;inset-x:0;top:0;z-index:9998;display:flex;justify-content:center;pointer-events:none;';
            wrap.innerHTML = '<div id="xp-res-banner" style="margin-top:1.5rem;padding:0.85rem 2.5rem;border-radius:1rem;background:linear-gradient(135deg,#facc15,#f97316);color:#1c1917;font-size:1.5rem;font-weight:900;letter-spacing:0.03em;box-shadow:0 8px 32px rgba(250,204,21,0.4);opacity:0;transform:scale(0.7);transition:all 0.45s cubic-bezier(.34,1.56,.64,1);">&#11088; +'+xp+' XP Earned!</div>';
            document.body.appendChild(wrap);
            requestAnimationFrame(function(){ requestAnimationFrame(function(){
                var el = document.getElementById('xp-res-banner');
                if (el) { el.style.opacity='1'; el.style.transform='scale(1)'; }
            }); });
            setTimeout(function(){
                var el = document.getElementById('xp-res-banner');
                if (el) { el.style.opacity='0'; el.style.transform='scale(0.75)'; }
                setTimeout(function(){ wrap.remove(); }, 500);
            }, 2800);
        }

        setTimeout(function(){
            spawnConfetti(70);
            showXPBanner({{ (int) $result->points_earned }});
        }, 350);
    })();
</script>
@endsection
@endif
