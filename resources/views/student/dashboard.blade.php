@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div>
            <p class="eyebrow">Network Training Portal</p>
            <h1>Welcome back, {{ auth()->user()->name }}!</h1>
            <p>
                Continue your networking learning journey, complete quiz challenges,
                earn points, and unlock the next stage.
            </p>
        </div>

        <div class="hero-symbol" aria-hidden="true">
            <i class="fa-solid fa-network-wired"></i>
        </div>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Player Overview</p>
        <h2 style="margin-bottom: 1.5rem;">Your Learning Status</h2>

        <div class="summary-grid">
            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    Total Experience Points
                </span>

                <strong class="stat-value">
                    {{ (int) $totalPoints }} XP
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Completed Topics
                </span>

                <strong class="stat-value">
                    {{ (int) $completedTopics }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    Overall Progress
                </span>

                <strong class="stat-value">
                    {{ number_format((float) $overallProgress, 0) }}%
                </strong>
            </div>
        </div>

        <div class="progress-track" style="margin-top: 1.5rem;">
            <div
                class="progress-fill"
                style="width: {{ min(100, max(0, (float) $overallProgress)) }}%;"
            ></div>
        </div>
    </section>

    <section>
        <p class="eyebrow">Mission Menu</p>
        <h2 style="margin-bottom: 1.5rem;">Choose Your Next Action</h2>

        <nav class="dashboard-grid">
            <a class="card" href="{{ route('student.learning') }}">
                <span class="card-icon" aria-hidden="true">
                    <i class="fa-solid fa-route"></i>
                </span>

                <h2>Learning Journey</h2>
                <p>
                    Start or continue your networking learning stages in order.
                </p>
            </a>

            <a class="card" href="{{ route('student.quiz') }}">
                <span class="card-icon" aria-hidden="true">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </span>

                <h2>Quiz Challenges</h2>
                <p>
                    Test your understanding and earn XP by completing quizzes.
                </p>
            </a>

            <a class="card" href="{{ route('student.progress') }}">
                <span class="card-icon" aria-hidden="true">
                    <i class="fa-solid fa-chart-line"></i>
                </span>

                <h2>My Progress</h2>
                <p>
                    Review your completed topics, quiz attempts, and total XP.
                </p>
            </a>
        </nav>
    </section>
@endsection