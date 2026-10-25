@php
    $active = $activePage ?? '';
@endphp

<div class="app-layout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar" aria-label="Lecturer Navigation">
        <div class="sidebar-header">
            <span class="sidebar-logo-icon" aria-hidden="true">
                <i class="fa-solid fa-network-wired"></i>
            </span>

            <span class="sidebar-logo-text">NetLearn</span>

            <button
                class="sidebar-close-btn"
                id="sidebarClose"
                aria-label="Close menu"
                type="button"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar" aria-hidden="true">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>

            <div class="user-info">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="user-role">{{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a
                href="{{ route('lecturer.dashboard') }}"
                class="nav-item {{ $active === 'dashboard' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-gauge-high"></i>
                </span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a
    href="{{ route('lecturer.topics.index') }}"
    class="nav-item {{ $active === 'topics' ? 'active' : '' }}"
>
    <span class="nav-icon" aria-hidden="true">
        <i class="fa-solid fa-book-open"></i>
    </span>
    <span class="nav-text">Manage Topics</span>
</a>

<a
    href="{{ route('lecturer.quizzes.index') }}"
    class="nav-item {{ $active === 'quizzes' ? 'active' : '' }}"
>
    <span class="nav-icon" aria-hidden="true">
        <i class="fa-solid fa-puzzle-piece"></i>
    </span>
    <span class="nav-text">Manage Quizzes</span>
</a>

<a
    href="{{ route('lecturer.student-performance.index') }}"
    class="nav-item {{ $active === 'performance' ? 'active' : '' }}"
>
    <span class="nav-icon" aria-hidden="true">
        <i class="fa-solid fa-users-viewfinder"></i>
    </span>
    <span class="nav-text">Student Performance</span>
</a>
        </nav>

        <div class="sidebar-footer">
            <button
                class="sidebar-theme-toggle"
                id="themeToggleBtn"
                type="button"
                aria-label="Toggle theme"
            >
                <span id="themeToggleIcon">☀️</span>
                <span id="themeToggleLabel">Light Mode</span>
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="nav-item logout-link">
                    <span class="nav-icon" aria-hidden="true">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </span>
                    <span class="nav-text">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <button
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="sidebar"
                type="button"
            >
                <span class="menu-bar"></span>
                <span class="menu-bar"></span>
                <span class="menu-bar"></span>
            </button>

            <div class="header-title">
                <h2>{{ $pageTitle ?? 'Lecturer Dashboard' }}</h2>
            </div>

            <div class="header-user-meta">
                <div class="header-user-avatar" aria-hidden="true">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>

                <div class="header-user-details">
                    <span class="user-meta-name">{{ auth()->user()->name }}</span>
                    <span class="user-meta-role">{{ ucfirst(auth()->user()->role) }}</span>
                </div>
            </div>
        </header>

        <main class="content-body">