@extends('layouts.app')

@section('content')
<section class="page-banner" style="margin-bottom: 24px;">
    <p class="eyebrow">Academic Credentials</p>
    <h1>Instructor Profile & Security</h1>
    <p>Manage your faculty credentials, contact information, and account access security.</p>
</section>

@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #4ade80; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.88rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if ($errors->any())
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; font-size: 0.88rem;">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Faculty Overview Card -->
<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 26px; margin-bottom: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.35); color: #c084fc; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 4px;">Faculty Profile</span>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; color: #FFFFFF;">{{ $user->name }}</h2>
                <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px; font-size: 0.82rem; color: var(--nl-text-secondary); flex-wrap: wrap;">
                    <span><i class="fa-solid fa-envelope" style="margin-right: 4px;"></i> {{ $user->email }}</span>
                    <span>&bull;</span>
                    <span class="status-pill pass" style="font-size: 0.7rem; padding: 2px 8px;">Lead Instructor</span>
                    @if ($user->staff_id)
                        <span>&bull;</span>
                        <span>Staff ID: <strong>{{ $user->staff_id }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--nl-text-tertiary); display: block; font-weight: 600;">Topics Authored</span>
                <strong style="font-size: 1.4rem; font-weight: 800; color: #FFFFFF;">{{ $taughtTopicsCount }}</strong>
            </div>
            <div style="width: 1px; height: 36px; background: var(--nl-glass-border);"></div>
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--nl-text-tertiary); display: block; font-weight: 600;">Evaluations Monitored</span>
                <strong style="font-size: 1.4rem; font-weight: 800; color: var(--nl-cyan);">{{ $totalEvaluationsMonitored }}</strong>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; align-items: start;">

    <!-- Left: Edit Personal & Faculty Details -->
    <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--nl-glass-border);">
            <i class="fa-solid fa-user-pen" style="color: var(--nl-cyan);"></i>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0; color: #FFFFFF;">Faculty Details</h3>
        </div>

        <form method="POST" action="{{ route('lecturer.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="form-row">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="macos-input"
                    value="{{ old('name', $user->name) }}"
                    required
                >
            </div>

            <div class="form-row">
                <label for="email">Email Address (Read-only)</label>
                <input
                    type="email"
                    id="email"
                    class="macos-input"
                    value="{{ $user->email }}"
                    disabled
                    style="opacity: 0.6; cursor: not-allowed;"
                >
                <span class="field-hint">Institutional login email cannot be changed directly.</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-row">
                    <label for="staff_id">Staff / Faculty ID</label>
                    <input
                        type="text"
                        id="staff_id"
                        name="staff_id"
                        class="macos-input"
                        placeholder="e.g. STF-8821"
                        value="{{ old('staff_id', $user->staff_id) }}"
                    >
                </div>

                <div class="form-row">
                    <label for="phone">Contact Phone</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        class="macos-input"
                        placeholder="+60 12-345 6789"
                        value="{{ old('phone', $user->phone) }}"
                    >
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-row">
                    <label for="faculty">Faculty</label>
                    <input
                        type="text"
                        id="faculty"
                        name="faculty"
                        class="macos-input"
                        placeholder="e.g. Computing & Informatics"
                        value="{{ old('faculty', $user->faculty) }}"
                    >
                </div>

                <div class="form-row">
                    <label for="department">Department</label>
                    <input
                        type="text"
                        id="department"
                        name="department"
                        class="macos-input"
                        placeholder="e.g. Computer Networks & Security"
                        value="{{ old('department', $user->department) }}"
                    >
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Profile Changes</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Right: Change Security Credentials -->
    <div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--nl-glass-border);">
            <i class="fa-solid fa-lock" style="color: #fbbf24;"></i>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0; color: #FFFFFF;">Account Security</h3>
        </div>

        <form method="POST" action="{{ route('lecturer.profile.password') }}">
            @csrf
            @method('PUT')

            <div class="form-row">
                <label for="current_password">Current Password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="macos-input"
                    required
                >
            </div>

            <div class="form-row">
                <label for="password">New Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="macos-input"
                    required
                >
                <span class="field-hint">Must be at least 8 characters.</span>
            </div>

            <div class="form-row">
                <label for="password_confirmation">Confirm New Password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="macos-input"
                    required
                >
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button secondary">
                    <i class="fa-solid fa-key"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
