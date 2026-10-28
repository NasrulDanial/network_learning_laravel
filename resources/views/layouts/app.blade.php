<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $pageTitle ?? 'NetLearn' }}</title>

    <script>
        (function() {
            // Pages shown inside an in-app drawer (e.g. lecturer preview) drop the outer app chrome
            if (window.self !== window.top) {
                document.documentElement.classList.add('is-embedded');
            }
            var saved = localStorage.getItem('netlearn-theme');
            if (saved === 'light' || (!saved && window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.setAttribute('data-theme', 'light');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/netlearn-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/netlearn-polish.css') }}">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @yield('head')

    <style>
        /* ── Gamified Micro-interactions (additive — does not override style.css) ── */

        /* Buttons: tactile press + hover lift */
        .button {
            transition: transform 0.12s ease, box-shadow 0.15s ease, background-color 0.2s ease !important;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(47, 128, 237, 0.35);
        }
        .button:active {
            transform: translateY(1px) scale(0.97);
            box-shadow: none;
        }

        /* Sidebar links: slide-right nudge on hover */
        .sidebar a {
            transition: color 0.2s ease, background-color 0.2s ease, transform 0.15s ease, padding-left 0.15s ease;
        }
        .sidebar a:hover { transform: translateX(3px); }

        /* Cards: subtle lift + glow border on hover */
        .card, .stage-card, .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease !important;
        }
        .card:hover, .stage-card.stage-unlocked:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.18), 0 0 0 1.5px var(--border-hover);
        }

        /* XP / stat values: pop in on page load */
        @keyframes stat-pop {
            0%   { transform: scale(0.7); opacity: 0; }
            70%  { transform: scale(1.08); }
            100% { transform: scale(1);   opacity: 1; }
        }
        .stat-value { animation: stat-pop 0.45s cubic-bezier(.34,1.56,.64,1) both; }
        .stat-card:nth-child(2) .stat-value { animation-delay: 0.08s; }
        .stat-card:nth-child(3) .stat-value { animation-delay: 0.16s; }
        .stat-card:nth-child(4) .stat-value { animation-delay: 0.24s; }

        /* Progress bar shimmer */
        @keyframes progress-shimmer {
            0%   { background-position: -200% center; }
            100% { background-position:  200% center; }
        }
        .progress-fill {
            background: linear-gradient(90deg,
                var(--primary) 0%,
                var(--cyan) 40%,
                #93c5fd 50%,
                var(--cyan) 60%,
                var(--primary) 100%);
            background-size: 200% 100%;
            animation: progress-shimmer 2.8s linear infinite;
            transition: width 0.8s cubic-bezier(.4,0,.2,1);
        }

        /* "In Progress" badges: soft pulse */
        .status-badge.status-progress {
            animation: badge-pulse 2s ease-in-out infinite;
        }
        @keyframes badge-pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.65; }
        }

        /* Completed stage markers: spin-in */
        .stage-completed .stage-marker {
            animation: spin-in 0.4s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes spin-in {
            0%   { transform: rotate(-90deg) scale(0.5); opacity: 0; }
            100% { transform: rotate(0) scale(1); opacity: 1; }
        }

        /* Achievement / result icons: bounce-in */
        .achievement-icon {
            animation: icon-bounce 0.6s cubic-bezier(.34,1.56,.64,1) 0.1s both;
        }
        @keyframes icon-bounce {
            0%   { transform: scale(0.5) rotate(-15deg); opacity: 0; }
            60%  { transform: scale(1.15) rotate(5deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        /* XP counter glow-on-hover */
        .xp-counter:hover {
            box-shadow: 0 0 18px rgba(250, 204, 21, 0.5), 0 0 40px rgba(250, 204, 21, 0.2);
        }
    </style>
</head>
<body>

    @auth
        @if (auth()->user()->role === 'student')
            @include('layouts.student_sidebar')
        @elseif (auth()->user()->role === 'lecturer')
            @include('layouts.lecturer_sidebar')
        @endif
    @endauth

    @yield('content')

    @include('layouts.footer')

    <script>window.NETLEARN_SOUND_DEFAULT = @json((bool) config('netlearn.sound_default_on'));</script>
    <script src="{{ asset('js/run/netfx.js') }}"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
    <script src="{{ asset('js/netlearn-app.js') }}"></script>

    @yield('scripts')

</body>
</html>
