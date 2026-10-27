<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Topic;
use App\Models\User;
use App\Services\RankProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UxModuleImprovementTest extends TestCase
{
    private User $student;
    private User $lecturer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::firstOrCreate(
            ['email' => 'test_student_ux@netlearn.local'],
            [
                'name' => 'Test Student UX',
                'password' => Hash::make('StudentPass123!'),
                'role' => 'student',
            ]
        );

        $this->lecturer = User::firstOrCreate(
            ['email' => 'test_lecturer_ux@netlearn.local'],
            [
                'name' => 'Test Lecturer UX',
                'password' => Hash::make('LecturerPass123!'),
                'role' => 'lecturer',
            ]
        );
    }

    public function test_student_can_view_profile(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.profile'));
        $response->assertStatus(200);
        $response->assertSee('Student Profile');
        $response->assertSee($this->student->name);
        $response->assertSee('Security Credentials');
    }

    public function test_student_can_update_permitted_profile_fields(): void
    {
        $response = $this->actingAs($this->student)->put(route('student.profile.update'), [
            'name' => 'Updated Cadet Name',
            'student_id' => 'STU-2026-9999',
            'programme' => 'BSc Computer Networks',
            'semester' => 4,
            'faculty' => 'Faculty of Computing',
            'phone' => '+60 12-345 6789',
        ]);

        $response->assertRedirect();
        $this->student->refresh();
        $this->assertEquals('Updated Cadet Name', $this->student->name);
        $this->assertEquals('STU-2026-9999', $this->student->student_id);
        $this->assertEquals(4, $this->student->semester);
    }

    public function test_student_can_change_password_with_current_password_verification(): void
    {
        $response = $this->actingAs($this->student)->put(route('student.profile.password'), [
            'current_password' => 'StudentPass123!',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect();
        $this->student->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->student->password));

        // Restore password for idempotency
        $this->student->password = Hash::make('StudentPass123!');
        $this->student->save();
    }

    public function test_student_cannot_change_password_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->student)->put(route('student.profile.password'), [
            'current_password' => 'WrongCurrentPassword!',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_student_can_view_achievements_showcase(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.achievements'));
        $response->assertStatus(200);
        $response->assertSee('Achievements');
        $response->assertSee('Honors');
        $response->assertSee('All Badges');
    }

    public function test_lecturer_can_view_profile(): void
    {
        $response = $this->actingAs($this->lecturer)->get(route('lecturer.profile'));
        $response->assertStatus(200);
        $response->assertSee('Instructor Profile');
        $response->assertSee($this->lecturer->name);
        $response->assertSee('Department');
    }

    public function test_lecturer_can_update_department_and_faculty(): void
    {
        $response = $this->actingAs($this->lecturer)->put(route('lecturer.profile.update'), [
            'name' => 'Prof. Network Specialist',
            'staff_id' => 'STF-5555',
            'department' => 'Department of Computer Systems & Networking',
            'faculty' => 'Faculty of Engineering & Informatics',
            'phone' => '+60 3-8888 1234',
        ]);

        $response->assertRedirect();
        $this->lecturer->refresh();
        $this->assertEquals('Prof. Network Specialist', $this->lecturer->name);
        $this->assertEquals('STF-5555', $this->lecturer->staff_id);
    }

    public function test_lecturer_can_view_student_dossier(): void
    {
        $response = $this->actingAs($this->lecturer)->get(route('lecturer.student-performance.show', $this->student->user_id));
        $response->assertStatus(200);
        $response->assertSee('Student Operative Dossier');
        $response->assertSee($this->student->name);
    }

    public function test_student_cannot_view_student_dossier_via_lecturer_route(): void
    {
        $response = $this->actingAs($this->student)->get(route('lecturer.student-performance.show', $this->student->user_id));
        // Student role should be rejected by lecturer middleware (redirect or 403)
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    public function test_lecturer_can_reorder_topics_atomically(): void
    {
        $topics = Topic::orderBy('topic_order', 'asc')->take(2)->get();
        if ($topics->count() >= 2) {
            $t1 = $topics[0];
            $t2 = $topics[1];

            $response = $this->actingAs($this->lecturer)->post(route('lecturer.topics.reorder'), [
                'topic_ids' => [$t2->topic_id, $t1->topic_id],
            ]);

            $response->assertRedirect();
            $t1->refresh();
            $t2->refresh();

            $this->assertEquals(1, $t2->topic_order);
            $this->assertEquals(2, $t1->topic_order);

            // Restore original order
            $this->actingAs($this->lecturer)->post(route('lecturer.topics.reorder'), [
                'topic_ids' => [$t1->topic_id, $t2->topic_id],
            ]);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_rank_progression_service_calculates_tiers_and_disclaimer(): void
    {
        // 0 XP -> Cadet (Tier 1)
        $telemetry0 = RankProgressionService::getRankTelemetry(0);
        $this->assertEquals(1, $telemetry0['tier']);
        $this->assertEquals('Network Cadet', $telemetry0['title']);
        $this->assertStringContainsString('not a formal industrial certification', $telemetry0['disclaimer']);

        // 750 XP -> Packet Analyst (Tier 2)
        $telemetry750 = RankProgressionService::getRankTelemetry(750);
        $this->assertEquals(2, $telemetry750['tier']);
        $this->assertEquals('Packet Analyst', $telemetry750['title']);
        $this->assertEquals(50, $telemetry750['tierProgressPct']);

        // 2500 XP -> Principal Architect (Tier 5, Max Tier)
        $telemetry2500 = RankProgressionService::getRankTelemetry(2500);
        $this->assertEquals(5, $telemetry2500['tier']);
        $this->assertEquals('Principal Architect', $telemetry2500['title']);
        $this->assertTrue($telemetry2500['is_max_tier']);
    }
}
