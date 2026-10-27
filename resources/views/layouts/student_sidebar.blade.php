@php
    $active = $activePage ?? '';
@endphp

<div class="app-layout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar" aria-label="Student Navigation">
        <div class="sidebar-header">
            <div class="sidebar-logo-group">
                <span class="sidebar-logo-icon" aria-hidden="true">
                    <i class="fa-solid fa-network-wired"></i>
                </span>
                <span class="sidebar-logo-text">NetLearn</span>
            </div>

            <button
                class="collapse-toggle"
                id="sidebarCollapseBtn"
                aria-label="Toggle sidebar collapse"
                title="Collapse Sidebar"
                type="button"
            >
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar" aria-hidden="true">
                <i class="fa-solid fa-user-astronaut"></i>
            </div>

            <div class="user-info">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="user-role">Student Operative</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a
                href="{{ route('student.dashboard') }}"
                class="nav-item {{ $active === 'dashboard' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-table-columns"></i>
                </span>
                <span class="nav-text">Dashboard</span>
            </a>

            <a
                href="{{ route('student.learning') }}"
                class="nav-item {{ $active === 'learning' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-diagram-project"></i>
                </span>
                <span class="nav-text">Learning Journey</span>
            </a>

            <a
                href="{{ route('student.quiz') }}"
                class="nav-item {{ $active === 'quiz' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <span class="nav-text">Topic Quizzes</span>
            </a>

            <a
                href="{{ route('student.progress') }}"
                class="nav-item {{ $active === 'progress' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
                <span class="nav-text">My Progress</span>
            </a>

            <a
                href="{{ route('student.achievements') }}"
                class="nav-item {{ $active === 'achievements' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-award"></i>
                </span>
                <span class="nav-text">Achievements</span>
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
                <button type="submit" class="nav-item logout-link" style="width: 100%; border: none; background: transparent; cursor: pointer;">
                    <span class="nav-icon" aria-hidden="true">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
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
                <h2>{{ $pageTitle ?? 'Operative Control Console' }}</h2>
            </div>

            @include('layouts.partials.header_account', ['profileRoute' => 'student.profile', 'roleLabel' => 'Student', 'accent' => 'cyan'])
        </header>

        <main class="content-body">
