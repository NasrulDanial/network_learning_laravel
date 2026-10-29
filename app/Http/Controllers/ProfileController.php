<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Services\RankProgressionService;
use App\Models\Progress;
use App\Models\Result;
use App\Models\UserAchievement;
use App\Models\UserMissionProgress;

class ProfileController extends Controller
{
    /**
     * Display student or lecturer profile.
     */
    public function show()
    {
        $user = auth()->user();

        if ($user->role === 'student') {
            // Aggregate telemetry for student profile
            $totalXp = \App\Services\XpService::total($user->user_id);

            $rankInfo = RankProgressionService::getRankTelemetry($totalXp);

            $earnedAchievements = UserAchievement::with('achievement')
                ->where('user_id', $user->user_id)
                ->orderByDesc('date_earned')
                ->get();

            $completedMissionsCount = UserMissionProgress::where('user_id', $user->user_id)
                ->where('is_completed', true)
                ->count();

            return view('student.profile', [
                'pageTitle' => 'Student Profile',
                'activePage' => 'profile',
                'user' => $user,
                'rankInfo' => $rankInfo,
                'earnedAchievements' => $earnedAchievements,
                'completedMissionsCount' => $completedMissionsCount,
                'totalXp' => $totalXp,
            ]);
        }

        // Lecturer Profile
        $taughtTopicsCount = \App\Models\Topic::where('created_by', $user->user_id)->count();
        $totalEvaluationsMonitored = Result::count();

        return view('lecturer.profile', [
            'pageTitle' => 'Instructor Profile',
            'activePage' => 'profile',
            'user' => $user,
            'taughtTopicsCount' => $taughtTopicsCount,
            'totalEvaluationsMonitored' => $totalEvaluationsMonitored,
        ]);
    }

    /**
     * Update permitted personal and academic fields.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if ($user->role === 'student') {
            $rules['student_id'] = ['nullable', 'string', 'max:50'];
            $rules['programme'] = ['nullable', 'string', 'max:150'];
            $rules['semester'] = ['nullable', 'integer', 'min:1', 'max:14'];
            $rules['faculty'] = ['nullable', 'string', 'max:150'];
        } else {
            $rules['staff_id'] = ['nullable', 'string', 'max:50'];
            $rules['department'] = ['nullable', 'string', 'max:150'];
            $rules['faculty'] = ['nullable', 'string', 'max:150'];
        }

        $validated = $request->validate($rules);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile details updated successfully.');
    }

    /**
     * Update account password securely.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Security credentials updated successfully.');
    }
}
