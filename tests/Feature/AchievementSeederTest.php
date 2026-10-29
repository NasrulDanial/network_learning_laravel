<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

class AchievementSeederTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        // The test database may already hold badges; start from none (rolled back after each test)
        UserAchievement::query()->delete();
        Achievement::query()->delete();
    }

    public function test_there_are_twenty_uniquely_named_badges_with_known_rules()
    {
        $this->seed(AchievementSeeder::class);
        $this->seed(AchievementSeeder::class);

        $badges = Achievement::all();
        $this->assertCount(20, $badges);
        $this->assertSame(20, $badges->pluck('badge_name')->unique()->count());
        foreach ($badges as $badge) {
            $this->assertArrayHasKey($badge->requirement_type, AchievementService::REQUIREMENT_LABELS, $badge->badge_name);
            $this->assertGreaterThan(0, $badge->requirement_value);
        }
    }

    public function test_renamed_badges_keep_the_students_who_already_earned_them()
    {
        $user = User::create(['name' => 'S', 'email' => 'ach_' . uniqid() . '@netlearn.test', 'password' => Hash::make('x'), 'role' => 'student']);
        $old = Achievement::create([
            'badge_name' => 'Packet Tracer Initiate', 'description' => 'old', 'requirement_type' => 'missions_completed',
            'requirement_value' => 1, 'badge_icon' => 'fa-network-wired', 'xp_reward' => 0,
        ]);
        UserAchievement::create(['user_id' => $user->user_id, 'achievement_id' => $old->achievement_id, 'date_earned' => now()]);

        $this->seed(AchievementSeeder::class);

        $this->assertSame('First Ping!', $old->fresh()->badge_name);
        $this->assertFalse(Achievement::where('badge_name', 'Packet Tracer Initiate')->exists());
        $this->assertTrue(UserAchievement::where('user_id', $user->user_id)->where('achievement_id', $old->achievement_id)->exists());
    }

    public function test_star_and_puzzle_badges_are_awarded_from_play()
    {
        $this->seed(AchievementSeeder::class);
        $this->setUpStudent();
        $puzzle = $this->makeIdentification($this->makeMission($this->makeTopic()));

        $response = $this->solve($puzzle);
        $badges = $response->json('new_badges');
        $this->assertContains('First Ping!', $badges);
        $this->assertContains('Triple Star', $badges);
        $this->assertSame(1, AchievementService::metrics($this->student->user_id)['three_star_puzzles']);
    }
}
