@extends('layouts.app')

@section('content')
<section class="page-banner" style="margin-bottom: 24px;">
    <p class="eyebrow">Academic Credentials</p>
    <h1>Instructor Profile & Security</h1>
    <p>Manage your faculty credentials, contact information, and account access security.</p>
</section>

@if (session('success'))
    <div class="message success" style="margin-bottom: 1.5rem;">
        <p style="margin: 0;"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="message error" style="margin-bottom: 1.5rem;">
        @foreach ($errors->all() as $error)
            <p style="margin-bottom: 0;">{{ $error }}</p>
        @endforeach
    </div>
@endif

{{-- Faculty Overview Card --}}
<div class="management-card" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 64px; height: 64px; border-radius: var(--radius-lg); background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.35); color: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <span class="eyebrow" style="margin-bottom: 4px;">Faculty Profile</span>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0;">{{ $user->name }}</h2>
                <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px; font-size: 0.82rem; color: var(--text-secondary); flex-wrap: wrap;">
                    <span><i class="fa-solid fa-envelope" style="margin-right: 4px;"></i> {{ $user->email }}</span>
                    <span>&bull;</span>
                    <span class="status-badge status-completed" style="font-size: 0.7rem;">Lead Instructor</span>
                    @if ($user->staff_id)
                        <span>&bull;</span>
                        <span>Staff ID: <strong>{{ $user->staff_id }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-secondary); display: block; font-weight: 600;">Topics Authored</span>
                <strong style="font-size: 1.4rem; font-weight: 800;">{{ $taughtTopicsCount }}</strong>
            </div>
            <div style="width: 1px; height: 36px; background: var(--border);"></div>
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-secondary); display: block; font-weight: 600;">Evaluations Monitored</span>
                <strong style="font-size: 1.4rem; font-weight: 800; color: var(--cyan);">{{ $totalEvaluationsMonitored }}</strong>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; align-items: start;">

    {{-- Left: Edit Personal & Faculty Details --}}
    <div class="management-card" style="margin-bottom: 0;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
            <i class="fa-solid fa-user-pen" style="color: var(--cyan);"></i>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0;">Department & Faculty Records</h3>
        </div>

        <form method="POST" action="{{ route('lecturer.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address (Read-only)</label>
                <input type="email" id="email" value="{{ $user->email }}" disabled style="opacity: 0.6; cursor: not-allowed;">
                <span class="field-help">Institutional login email cannot be changed directly.</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="staff_id">Staff / Faculty ID</label>
                    <input type="text" id="staff_id" name="staff_id" placeholder="e.g. STF-8821" value="{{ old('staff_id', $user->staff_id) }}">
                </div>

                <div class="form-group">
                    <label for="phone">Contact Phone</label>
                    <input type="text" id="phone" name="phone" placeholder="+60 12-345 6789" value="{{ old('phone', $user->phone) }}">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="faculty">Faculty</label>
                    <input type="text" id="faculty" name="faculty" placeholder="e.g. Computing & Informatics" value="{{ old('faculty', $user->faculty) }}">
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" placeholder="e.g. Computer Networks & Security" value="{{ old('department', $user->department) }}">
                </div>
            </div>

            <div style="margin-top: 8px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Profile Changes</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Right: Change Security Credentials --}}
    <div class="management-card" id="change-password" style="margin-bottom: 0; scroll-margin-top: 90px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
            <i class="fa-solid fa-lock" style="color: #fbbf24;"></i>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0;">Account Security</h3>
        </div>

        <form method="POST" action="{{ route('lecturer.profile.password') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
                <span class="field-help">Must be at least 8 characters.</span>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <div style="margin-top: 8px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button secondary">
                    <i class="fa-solid fa-key"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>

</div>

<style>
    @media (max-width: 860px) {
        div[style*="grid-template-columns: 1.2fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection
