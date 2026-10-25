@extends('layouts.auth')

@section('content')
    <div class="auth-shell">
        <section class="auth-hero">
            <a href="{{ route('landing') }}" class="auth-brand">
                <span class="auth-brand-icon">
                    <i class="fa-solid fa-network-wired"></i>
                </span>
                <span>NetLearn</span>
            </a>

            <h1>Welcome Back</h1>

            <p>
                Continue your gamified computer networking learning journey.
                Login to access your dashboard, learning stages, quizzes,
                and progress tracking.
            </p>

            <div class="auth-feature-list">
                <span>
                    <i class="fa-solid fa-route"></i>
                    Learning Journey
                </span>

                <span>
                    <i class="fa-solid fa-puzzle-piece"></i>
                    Quiz Challenges
                </span>

                <span>
                    <i class="fa-solid fa-chart-line"></i>
                    Progress Tracking
                </span>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <div>
                    <p class="eyebrow">Authentication</p>
                    <h2>Login</h2>
                </div>

                <button
                    class="theme-toggle"
                    id="themeToggleBtn"
                    type="button"
                    aria-label="Toggle theme"
                >
                    <span id="themeToggleIcon">☀️</span>
                </button>
            </div>

            @if (session('success'))
                <div class="message success" style="margin-bottom: 1rem;">
                    <p style="margin-bottom: 0;">
                        {{ session('success') }}
                    </p>
                </div>
            @endif

            @if ($errors->any())
                <div class="message error" style="margin-bottom: 1rem;">
                    @foreach ($errors->all() as $error)
                        <p style="margin-bottom: 0;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" style="width: 100%;">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                    Login
                </button>
            </form>

            <p class="auth-switch">
                Do not have an account?
                <a href="{{ route('register') }}">Create account</a>
            </p>
        </section>
    </div>
@endsection