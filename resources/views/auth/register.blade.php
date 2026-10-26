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

            <h1>Start Learning Networking</h1>

            <p>
                Create an account to access interactive learning stages,
                quiz challenges, XP rewards, and progress tracking.
            </p>

            <div class="auth-feature-list">
                <span>
                    <i class="fa-solid fa-user-graduate"></i>
                    Student Access
                </span>

                <span>
                    <i class="fa-solid fa-chalkboard-user"></i>
                    Lecturer Access
                </span>

                <span>
                    <i class="fa-solid fa-star"></i>
                    XP Reward System
                </span>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <div>
                    <p class="eyebrow">Create Account</p>
                    <h2>Register</h2>
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

            @if ($errors->any())
                <div class="message error" style="margin-bottom: 1rem;">
                    @foreach ($errors->all() as $error)
                        <p style="margin-bottom: 0;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.submit') }}">
                @csrf

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Enter your name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Minimum 6 characters"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Repeat password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Create Student Account
                </button>
            </form>

            <p class="auth-switch">
                Already have an account?
                <a href="{{ route('login') }}">Login here</a>
            </p>
        </section>
    </div>
@endsection