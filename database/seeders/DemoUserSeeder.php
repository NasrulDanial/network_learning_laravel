<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts for local development and testing only (password: password123).
 * Never runs in production. Existing accounts are left untouched.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            if ($this->command) {
                $this->command->warn('   Demo users skipped: only seeded in local/testing environments.');
            }
            return;
        }

        $accounts = [
            ['lecturer@test.com', 'lecturer1', 'lecturer', ['staff_id' => 'LEC-0001', 'faculty' => 'Faculty of Computer and Mathematical Sciences', 'department' => 'Computer Networks']],
            ['student@test.com', 'student1', 'student', ['student_id' => '2026000001', 'programme' => 'Diploma in Computer Science', 'semester' => 3, 'faculty' => 'Faculty of Computer and Mathematical Sciences']],
        ];

        foreach ($accounts as [$email, $name, $role, $profile]) {
            User::firstOrCreate(
                ['email' => $email],
                array_merge(['name' => $name, 'password' => Hash::make('password123'), 'role' => $role], $profile)
            );
        }

        if ($this->command) {
            $this->command->info('   Demo users: lecturer@test.com / student@test.com (password123) ensured');
        }
    }
}
