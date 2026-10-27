@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Success & Error Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
            <i class="fas fa-check-circle text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile Header Banner -->
    <div class="glass-card p-6 lg:p-8 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-18 h-18 lg:w-20 lg:h-20 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-blue-600/30 border border-cyan-500/30 flex items-center justify-center text-3xl font-bold text-cyan-400 font-mono shadow-lg shadow-cyan-500/10">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <h1 class="text-2xl font-bold text-white tracking-tight">{{ $user->name }}</h1>
                        <span class="badge-cyber badge-cyber-primary text-xs">
                            <i class="fas fa-user-graduate mr-1"></i> Student Cadet
                        </span>
                        <span class="badge-cyber badge-cyber-warning text-xs font-mono">
                            <i class="fas fa-shield-alt mr-1"></i> Tier {{ $rankInfo['tier'] }}: {{ $rankInfo['title'] }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 font-mono flex items-center gap-3">
                        <span><i class="fas fa-envelope mr-1 text-slate-500"></i> {{ $user->email }}</span>
                        @if($user->student_id)
                            <span><i class="fas fa-id-card mr-1 text-slate-500"></i> {{ $user->student_id }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- XP & Rank Progress Bar -->
            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700/60 min-w-[260px]">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="text-slate-400 font-mono">Clearance Progress</span>
                    <span class="text-cyan-400 font-mono font-bold">{{ $rankInfo['tierProgressPct'] }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden mb-2">
                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 transition-all duration-500" 
                         style="width: {{ $rankInfo['tierProgressPct'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
                    <span>{{ number_format($totalXp) }} XP</span>
                    @if($rankInfo['nextTierXp'])
                        <span>Next: {{ number_format($rankInfo['nextTierXp']) }} XP</span>
                    @else
                        <span class="text-amber-400">Max Rank</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Split Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Academic Profile Form (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                    <div>
                        <h2 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                            <i class="fas fa-user-edit text-cyan-400"></i> Academic & Personal Records
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">Keep your matriculation and academic details current.</p>
                    </div>
                    <span class="text-[11px] font-mono text-slate-500 uppercase">Self-Managed</span>
                </div>

                <form action="{{ route('student.profile.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Full Legal / Matric Name <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        </div>

                        <!-- Email (Read-Only) -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Institutional Email
                            </label>
                            <input type="email" id="email" value="{{ $user->email }}" disabled
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-800/40 border border-slate-800 text-slate-400 text-sm cursor-not-allowed">
                            <p class="text-[11px] text-slate-500 mt-1">Managed via authentication provider.</p>
                        </div>

                        <!-- Student Matriculation ID -->
                        <div>
                            <label for="student_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Student ID / Matric No.
                            </label>
                            <input type="text" id="student_id" name="student_id" value="{{ old('student_id', $user->student_id) }}" placeholder="e.g. B032110045"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        </div>

                        <!-- Programme of Study -->
                        <div>
                            <label for="programme" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Programme of Study
                            </label>
                            <input type="text" id="programme" name="programme" value="{{ old('programme', $user->programme) }}" placeholder="e.g. BACS (Computer Networking)"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        </div>

                        <!-- Semester -->
                        <div>
                            <label for="semester" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Current Semester
                            </label>
                            <select id="semester" name="semester"
                                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                                <option value="">Select Semester...</option>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('semester', $user->semester) == $i ? 'selected' : '' }}>
                                        Semester {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Faculty -->
                        <div>
                            <label for="faculty" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Faculty / School
                            </label>
                            <input type="text" id="faculty" name="faculty" value="{{ old('faculty', $user->faculty) }}" placeholder="e.g. Faculty of Information and Communication Technology"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        </div>

                        <!-- Contact Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Contact Phone
                            </label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. +60 12-345 6789"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" class="btn-cyber btn-cyber-primary text-xs">
                            <i class="fas fa-save mr-1.5"></i> Save Profile Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Security & Badges Preview (1 col) -->
        <div class="space-y-6">
            <!-- Password Security Form -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-800">
                    <i class="fas fa-key text-amber-400"></i>
                    <h2 class="text-base font-bold text-white tracking-tight">Security Credentials</h2>
                </div>

                <form action="{{ route('student.profile.password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Current Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-3.5 py-2 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            New Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3.5 py-2 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        <p class="text-[11px] text-slate-500 mt-1">Minimum 8 characters with mixed casing & numbers.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Confirm New Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-3.5 py-2 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full btn-cyber btn-cyber-secondary text-xs">
                            <i class="fas fa-lock mr-1.5"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Earned Honors Showcase Preview -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-award text-yellow-400"></i>
                        <h2 class="text-base font-bold text-white tracking-tight">Earned Honors</h2>
                    </div>
                    <span class="text-xs font-mono text-cyan-400 font-bold">
                        {{ $earnedAchievements->count() }} Unlocked
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($earnedAchievements->take(3) as $ua)
                        <div class="p-3 rounded-xl bg-slate-900/40 border border-slate-800/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 flex items-center justify-center text-lg shrink-0">
                                <i class="fas {{ $ua->achievement->badge_icon ?? 'fa-award' }}"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-white truncate">{{ $ua->achievement->badge_name }}</h4>
                                <p class="text-[11px] text-slate-400 font-mono truncate">
                                    {{ \Carbon\Carbon::parse($ua->date_earned)->format('M d, Y') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 py-3 text-center">No badges unlocked yet. Complete missions to unlock achievements.</p>
                    @endforelse
                </div>

                <div class="pt-4 mt-3 border-t border-slate-800/80">
                    <a href="{{ route('student.achievements') }}" class="w-full btn-cyber btn-cyber-secondary text-xs flex items-center justify-center gap-2">
                        <i class="fas fa-medal"></i> View All Achievements
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
