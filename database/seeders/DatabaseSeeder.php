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
            CurriculumSeeder::class,   // 5 topics, 15 missions, 45 puzzles, 50 quiz questions
            AchievementSeeder::class,  // badge definitions with exact criteria
        ]);
    }
}
