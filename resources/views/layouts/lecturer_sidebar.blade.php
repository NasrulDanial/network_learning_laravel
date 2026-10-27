@php
    $active = $activePage ?? '';
@endphp

<div class="app-layout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar" aria-label="Lecturer Navigation">
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
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>

            <div class="user-info">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="user-role">Lead Instructor</span>
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
                    <i class="fa-solid fa-diagram-project"></i>
                </span>
                <span class="nav-text">Manage Topics</span>
            </a>

            <a
                href="{{ route('lecturer.missions.index') }}"
                class="nav-item {{ $active === 'missions' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </span>
                <span class="nav-text">Manage Missions (PBL)</span>
            </a>

            <a
                href="{{ route('lecturer.quizzes.index') }}"
                class="nav-item {{ $active === 'quizzes' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <span class="nav-text">Manage Quizzes</span>
            </a>

            <a
                href="{{ route('lecturer.student-performance.index') }}"
                class="nav-item {{ $active === 'performance' ? 'active' : '' }}"
            >
                <span class="nav-icon" aria-hidden="true">
                    <i class="fa-solid fa-chart-pie"></i>
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
                <h2>{{ $pageTitle ?? 'Instructor Mission Control' }}</h2>
            </div>

            @include('layouts.partials.header_account', ['profileRoute' => 'lecturer.profile', 'roleLabel' => 'Lecturer', 'accent' => 'purple'])
        </header>

        <main class="content-body">
