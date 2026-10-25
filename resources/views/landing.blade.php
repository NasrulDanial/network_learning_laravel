<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NetLearn — Gamified Networking Learning Platform</title>

    <script>
        if (localStorage.getItem('netlearn-theme') === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >
</head>
<body class="landing-page">

    <nav class="landing-nav">
        <a href="{{ route('landing') }}" class="landing-logo">
            <span class="landing-logo-icon">
                <i class="fa-solid fa-network-wired"></i>
            </span>

            <span class="landing-logo-text">
                NetLearn
            </span>
        </a>

        <div class="landing-actions">
            <button
                class="theme-toggle"
                id="themeToggleBtn"
                type="button"
                aria-label="Toggle theme"
            >
                <span id="themeToggleIcon">☀️</span>
            </button>

            <a class="button secondary" href="{{ route('login') }}">
                Login
            </a>

            <a class="button" href="{{ route('register') }}">
                Register
            </a>
        </div>
    </nav>

    <main class="landing-hero">
        <span class="landing-badge">
            <i class="fa-solid fa-network-wired" aria-hidden="true"></i>
            Interactive Computer Networking Learning
        </span>

        <h1>
            Learn Networking Concepts Through Gamified Challenges
        </h1>

        <p>
            NetLearn helps students understand OSI, TCP/IP, and basic networking
            concepts through structured learning stages, quizzes, XP rewards,
            and progress tracking.
        </p>

        <div class="landing-actions">
            <a class="button" href="{{ route('register') }}">
                <i class="fa-solid fa-play" aria-hidden="true"></i>
                Get Started
            </a>

            <a class="button secondary" href="{{ route('login') }}">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                Login
            </a>
        </div>

        <section class="landing-feature-grid">
            <article class="card">
                <span class="card-icon">
                    <i class="fa-solid fa-route"></i>
                </span>

                <h2>Learning Journey</h2>

                <p>
                    Students complete networking topics step-by-step through
                    locked and unlocked stages.
                </p>
            </article>

            <article class="card">
                <span class="card-icon">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </span>

                <h2>Quiz Challenges</h2>

                <p>
                    Each topic includes quiz challenges to test student
                    understanding and unlock progress.
                </p>
            </article>

            <article class="card">
                <span class="card-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </span>

                <h2>Progress Tracking</h2>

                <p>
                    Students can monitor completed topics, quiz results,
                    XP points, and learning progress.
                </p>
            </article>
        </section>
    </main>

    <script src="{{ asset('js/theme.js') }}"></script>
</body>
</html>