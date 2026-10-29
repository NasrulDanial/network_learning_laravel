<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Every seeder here is idempotent and never overwrites existing content,
     * so `php artisan db:seed` is safe to re-run.
     */
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,     // local/testing only
            CurriculumSeeder::class,   // 10 stages, 30 missions, 90 puzzles, 150 quiz questions
            AchievementSeeder::class,  // 20 badges with exact criteria
        ]);
    }
}
