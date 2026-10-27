@extends('layouts.app')

@section('content')
    {{-- Success & Error Flash Messages --}}
    @if(session('success'))
        <div class="message success" style="margin-bottom: 1.5rem;">
            <p style="margin: 0;"><i class="fa-solid fa-check-circle"></i> {{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="message error" style="margin-bottom: 1.5rem;">
            @foreach($errors->all() as $error)
                <p style="margin-bottom: 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Profile Header Banner --}}
    <section class="page-banner" style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-lg); background: rgba(34, 211, 238, 0.12); border: 1px solid rgba(34, 211, 238, 0.3); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 800; color: var(--cyan);">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 4px;">
                        <h1 style="margin: 0; font-size: 1.5rem;">{{ $user->name }}</h1>
                        <span class="status-badge status-progress">
                            <i class="fa-solid fa-user-graduate"></i> Student
                        </span>
                        <span class="status-badge status-completed">
                            <i class="fa-solid fa-shield-alt"></i>
                            Tier {{ $rankInfo['tier'] }}: {{ $rankInfo['title'] }}
                        </span>
                    </div>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">
                        <i class="fa-solid fa-envelope" style="margin-right: 4px;"></i> {{ $user->email }}
                        @if($user->student_id)
                            &bull;
                            <i class="fa-solid fa-id-card" style="margin-right: 4px;"></i> {{ $user->student_id }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- XP Progression --}}
            <div class="stat-card" style="min-width: 250px; margin-bottom: 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.78rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;">Clearance Progress</span>
                    <strong style="font-size: 0.85rem; color: var(--cyan);">{{ $rankInfo['tierProgressPct'] }}%</strong>
                </div>
                <div class="progress-track" style="margin: 4px 0;">
                    <div class="progress-fill" style="width: {{ $rankInfo['tierProgressPct'] }}%;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-secondary); margin-top: 4px;">
                    <span>{{ number_format($totalXp) }} XP</span>
                    @if($rankInfo['nextTierXp'])
                        <span>Next: {{ number_format($rankInfo['nextTierXp']) }} XP</span>
                    @else
                        <span style="color: #fbbf24;">Max Rank</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
        {{-- Left Column: Academic Profile Form --}}
        <div>
            <div class="management-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                    <div>
                        <h2 style="margin-bottom: 4px;">
                            <i class="fa-solid fa-user-edit" style="color: var(--cyan); margin-right: 8px;"></i>
                            Academic & Personal Records
                        </h2>
                        <p style="font-size: 0.82rem; margin: 0;">Keep your matriculation and academic details current.</p>
                    </div>
                </div>

                <form action="{{ route('student.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        {{-- Full Name --}}
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="name">Full Name <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>

                        {{-- Email (Read-Only) --}}
                        <div class="form-group">
                            <label for="email">Institutional Email</label>
                            <input type="email" id="email" value="{{ $user->email }}" disabled style="opacity: 0.6; cursor: not-allowed;">
                            <span class="field-help">Managed via authentication provider.</span>
                        </div>

                        {{-- Student ID --}}
                        <div class="form-group">
                            <label for="student_id">Student ID / Matric No.</label>
                            <input type="text" id="student_id" name="student_id" value="{{ old('student_id', $user->student_id) }}" placeholder="e.g. B032110045">
                        </div>

                        {{-- Programme --}}
                        <div class="form-group">
                            <label for="programme">Programme of Study</label>
                            <input type="text" id="programme" name="programme" value="{{ old('programme', $user->programme) }}" placeholder="e.g. BACS (Computer Networking)">
                        </div>

                        {{-- Semester --}}
                        <div class="form-group">
                            <label for="semester">Current Semester</label>
                            <select id="semester" name="semester">
                                <option value="">Select Semester...</option>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('semester', $user->semester) == $i ? 'selected' : '' }}>
                                        Semester {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- Faculty --}}
                        <div class="form-group">
                            <label for="faculty">Faculty / School</label>
                            <input type="text" id="faculty" name="faculty" value="{{ old('faculty', $user->faculty) }}" placeholder="e.g. Faculty of ICT">
                        </div>

                        {{-- Phone --}}
                        <div class="form-group">
                            <label for="phone">Contact Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. +60 12-345 6789">
                        </div>
                    </div>

                    <div style="padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; margin-top: 8px;">
                        <button type="submit" class="button" style="padding: 10px 20px; font-size: 0.88rem;">
                            <i class="fa-solid fa-save"></i> Save Profile Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Right Column: Password & Honors --}}
        <div style="display: flex; flex-direction: column; gap: 24px;">
            {{-- Password Form --}}
            <div class="management-card" style="margin-bottom: 0;">
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                    <i class="fa-solid fa-key" style="color: #fbbf24;"></i> Security Credentials
                </h3>

                <form action="{{ route('student.profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="current_password">Current Password <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <span class="field-help">Minimum 8 characters.</span>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm New Password <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <button type="submit" class="button secondary" style="width: 100%; margin-top: 4px;">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </form>
            </div>

            {{-- Earned Honors Preview --}}
            <div class="management-card" style="margin-bottom: 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-award" style="color: #fbbf24;"></i> Earned Honors
                    </h3>
                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--cyan);">
                        {{ $earnedAchievements->count() }} Unlocked
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @forelse($earnedAchievements->take(3) as $ua)
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: var(--radius-md); background: var(--bg-secondary); border: 1px solid var(--border);">
                            <div style="width: 40px; height: 40px; border-radius: var(--radius-sm); background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                <i class="fa-solid {{ $ua->achievement->badge_icon ?? 'fa-award' }}"></i>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <strong style="display: block; font-size: 0.88rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $ua->achievement->badge_name }}</strong>
                                <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                    {{ \Carbon\Carbon::parse($ua->date_earned)->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p style="text-align: center; color: var(--text-secondary); font-size: 0.85rem; padding: 16px 0; margin: 0;">
                            No badges unlocked yet.
                        </p>
                    @endforelse
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border);">
                    <a href="{{ route('student.achievements') }}" class="button secondary" style="width: 100%; font-size: 0.85rem;">
                        <i class="fa-solid fa-medal"></i> View All Achievements
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 860px) {
            div[style*="grid-template-columns: 2fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            .form-group[style*="grid-column: span 2"] {
                grid-column: span 1 !important;
            }
        }
    </style>
@endsection
