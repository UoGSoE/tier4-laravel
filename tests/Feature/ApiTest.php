<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function we_need_a_valid_api_key_to_make_requests(): void
    {
        $response = $this->getJson('/api/students', [
            'Authorization' => 'Bearer invalid-key',
        ]);

        $response->assertUnauthorized();

        config(['tier4.api_key' => 'valid-key']);

        $response = $this->getJson('/api/students', [
            'Authorization' => 'Bearer invalid-key',
        ]);

        $response->assertUnauthorized();

        $response = $this->getJson('/api/students', [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function we_can_get_a_list_of_all_overdue_tier4_meetings(): void
    {
        config(['tier4.meeting_reminder_days' => 30]);
        config(['tier4.api_key' => 'valid-key']);
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();
        $student4 = Student::factory()->create();
        $inactiveStudent = Student::factory()->create(['is_active' => false]);
        $intimeMeeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(10), 'student_id' => $student1->id, 'supervisor_id' => $student1->supervisor_id]);
        $intimeMeeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(15), 'student_id' => $student2->id, 'supervisor_id' => $student2->supervisor_id]);
        $overdueMeeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(35), 'student_id' => $student3->id, 'supervisor_id' => $student3->supervisor_id]);
        $overdueMeeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $student4->id, 'supervisor_id' => $student4->supervisor_id]);
        $overdueMeetingWithInactiveStudent = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $inactiveStudent->id, 'supervisor_id' => $inactiveStudent->supervisor_id]);

        $response = $this->getJson('/api/overduemeetings', [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'last_meeting_at' => $overdueMeeting1->meeting_at->format('Y-m-d'),
            'student_id' => $overdueMeeting1->student_id,
            'student_name' => $overdueMeeting1->student->full_name,
            'student_username' => $overdueMeeting1->student->username,
            'student_email' => $overdueMeeting1->student->email,
            'supervisor_id' => $overdueMeeting1->supervisor_id,
            'supervisor_name' => $overdueMeeting1->supervisor->full_name,
            'supervisor_username' => $overdueMeeting1->supervisor->username,
            'supervisor_email' => $overdueMeeting1->supervisor->email,
        ]);
        $response->assertJsonFragment([
            'last_meeting_at' => $overdueMeeting2->meeting_at->format('Y-m-d'),
            'student_id' => $overdueMeeting2->student_id,
            'student_name' => $overdueMeeting2->student->full_name,
            'student_username' => $overdueMeeting2->student->username,
            'student_email' => $overdueMeeting2->student->email,
            'supervisor_id' => $overdueMeeting2->supervisor_id,
            'supervisor_name' => $overdueMeeting2->supervisor->full_name,
            'supervisor_username' => $overdueMeeting2->supervisor->username,
            'supervisor_email' => $overdueMeeting2->supervisor->email,
        ]);
    }

    /** @test */
    public function we_can_get_a_list_of_all_overdue_tier4_meetings_for_phd_students(): void
    {
        config(['tier4.meeting_reminder_days' => 30]);
        config(['tier4.api_key' => 'valid-key']);
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $student4 = Student::factory()->create();
        $inactiveStudent = Student::factory()->create(['is_active' => false]);
        $intimeMeeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(10), 'student_id' => $student1->id, 'supervisor_id' => $student1->supervisor_id]);
        $intimeMeeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(15), 'student_id' => $student2->id, 'supervisor_id' => $student2->supervisor_id]);
        $overdueMeeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(35), 'student_id' => $student3->id, 'supervisor_id' => $student3->supervisor_id]);
        $overdueMeeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $student4->id, 'supervisor_id' => $student4->supervisor_id]);
        $overdueMeetingWithInactiveStudent = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $inactiveStudent->id, 'supervisor_id' => $inactiveStudent->supervisor_id]);

        $response = $this->getJson('/api/overduemeetings?type='.Student::TYPE_PHD, [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'last_meeting_at' => $overdueMeeting2->meeting_at->format('Y-m-d'),
            'student_id' => $overdueMeeting2->student_id,
            'student_name' => $overdueMeeting2->student->full_name,
            'student_username' => $overdueMeeting2->student->username,
            'student_email' => $overdueMeeting2->student->email,
            'supervisor_id' => $overdueMeeting2->supervisor_id,
            'supervisor_name' => $overdueMeeting2->supervisor->full_name,
            'supervisor_username' => $overdueMeeting2->supervisor->username,
            'supervisor_email' => $overdueMeeting2->supervisor->email,
        ]);
    }

    /** @test */
    public function we_can_get_a_list_of_all_overdue_tier4_meetings_for_postgrad_project_students(): void
    {
        config(['tier4.meeting_reminder_days' => 30]);
        config(['tier4.api_key' => 'valid-key']);
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $student4 = Student::factory()->create();
        $inactiveStudent = Student::factory()->create(['is_active' => false]);
        $intimeMeeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(10), 'student_id' => $student1->id, 'supervisor_id' => $student1->supervisor_id]);
        $intimeMeeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(15), 'student_id' => $student2->id, 'supervisor_id' => $student2->supervisor_id]);
        $overdueMeeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(35), 'student_id' => $student3->id, 'supervisor_id' => $student3->supervisor_id]);
        $overdueMeeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $student4->id, 'supervisor_id' => $student4->supervisor_id]);
        $overdueMeetingWithInactiveStudent = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $inactiveStudent->id, 'supervisor_id' => $inactiveStudent->supervisor_id]);

        $response = $this->getJson('/api/overduemeetings?type='.Student::TYPE_POSTGRAD_PROJECT, [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'last_meeting_at' => $overdueMeeting1->meeting_at->format('Y-m-d'),
            'student_id' => $overdueMeeting1->student_id,
            'student_name' => $overdueMeeting1->student->full_name,
            'student_username' => $overdueMeeting1->student->username,
            'student_email' => $overdueMeeting1->student->email,
            'supervisor_id' => $overdueMeeting1->supervisor_id,
            'supervisor_name' => $overdueMeeting1->supervisor->full_name,
            'supervisor_username' => $overdueMeeting1->supervisor->username,
            'supervisor_email' => $overdueMeeting1->supervisor->email,
        ]);
    }

    /** @test */
    public function we_can_get_a_list_of_all_students_and_their_last_meeting(): void
    {
        config(['tier4.api_key' => 'valid-key']);
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();
        $invactiveStudent = Student::factory()->create(['is_active' => false]);
        $meeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(10), 'student_id' => $student1->id, 'supervisor_id' => $student1->supervisor_id]);
        $meeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(15), 'student_id' => $student2->id, 'supervisor_id' => $student2->supervisor_id]);
        $meeting3 = Meeting::factory()->create(['meeting_at' => now()->subDays(20), 'student_id' => $student3->id, 'supervisor_id' => $student3->supervisor_id]);
        $olderMeeting3 = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $student3->id, 'supervisor_id' => $student3->supervisor_id]);

        $response = $this->getJson('/api/students', [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment([
            'last_meeting_at' => $meeting1->meeting_at->format('Y-m-d'),
            'student_id' => $meeting1->student_id,
            'student_name' => $meeting1->student->full_name,
            'student_username' => $meeting1->student->username,
            'student_email' => $meeting1->student->email,
            'supervisor_id' => $meeting1->supervisor_id,
            'supervisor_name' => $meeting1->supervisor->full_name,
            'supervisor_username' => $meeting1->supervisor->username,
            'supervisor_email' => $meeting1->supervisor->email,
        ]);
        $response->assertJsonFragment([
            'last_meeting_at' => $meeting2->meeting_at->format('Y-m-d'),
            'student_id' => $meeting2->student_id,
            'student_name' => $meeting2->student->full_name,
            'student_username' => $meeting2->student->username,
            'student_email' => $meeting2->student->email,
            'supervisor_id' => $meeting2->supervisor_id,
            'supervisor_name' => $meeting2->supervisor->full_name,
            'supervisor_username' => $meeting2->supervisor->username,
            'supervisor_email' => $meeting2->supervisor->email,
        ]);
        $response->assertJsonFragment([
            'last_meeting_at' => $meeting3->meeting_at->format('Y-m-d'),
            'student_id' => $meeting3->student_id,
            'student_name' => $meeting3->student->full_name,
            'student_username' => $meeting3->student->username,
            'student_email' => $meeting3->student->email,
            'supervisor_id' => $meeting3->supervisor_id,
            'supervisor_name' => $meeting3->supervisor->full_name,
            'supervisor_username' => $meeting3->supervisor->username,
            'supervisor_email' => $meeting3->supervisor->email,
        ]);
    }

    /** @test */
    public function we_can_get_a_list_of_all_phd_students_and_their_last_meeting(): void
    {
        config(['tier4.api_key' => 'valid-key']);
        $phdStudent1 = Student::factory()->create(['type' => Student::TYPE_PHD]);
        $phdStudent2 = Student::factory()->create(['type' => Student::TYPE_PHD]);
        $postgradProjectStudent = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $invactivePhdStudent = Student::factory()->create(['is_active' => false, 'type' => Student::TYPE_PHD]);
        $meeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(10), 'student_id' => $phdStudent1->id, 'supervisor_id' => $phdStudent1->supervisor_id]);
        $meeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(15), 'student_id' => $phdStudent2->id, 'supervisor_id' => $phdStudent2->supervisor_id]);
        $meeting3 = Meeting::factory()->create(['meeting_at' => now()->subDays(20), 'student_id' => $postgradProjectStudent->id, 'supervisor_id' => $postgradProjectStudent->supervisor_id]);
        $inactivePhdMeeting = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $invactivePhdStudent->id, 'supervisor_id' => $invactivePhdStudent->supervisor_id]);

        $response = $this->getJson('/api/students/?type='.Student::TYPE_PHD, [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'last_meeting_at' => $meeting1->meeting_at->format('Y-m-d'),
            'student_id' => $meeting1->student_id,
            'student_name' => $meeting1->student->full_name,
            'student_username' => $meeting1->student->username,
            'student_email' => $meeting1->student->email,
            'supervisor_id' => $meeting1->supervisor_id,
            'supervisor_name' => $meeting1->supervisor->full_name,
            'supervisor_username' => $meeting1->supervisor->username,
            'supervisor_email' => $meeting1->supervisor->email,
        ]);
        $response->assertJsonFragment([
            'last_meeting_at' => $meeting2->meeting_at->format('Y-m-d'),
            'student_id' => $meeting2->student_id,
            'student_name' => $meeting2->student->full_name,
            'student_username' => $meeting2->student->username,
            'student_email' => $meeting2->student->email,
            'supervisor_id' => $meeting2->supervisor_id,
            'supervisor_name' => $meeting2->supervisor->full_name,
            'supervisor_username' => $meeting2->supervisor->username,
            'supervisor_email' => $meeting2->supervisor->email,
        ]);
    }

    /** @test */
    public function we_can_get_a_list_of_all_postgrad_project_students_and_their_last_meeting(): void
    {
        config(['tier4.api_key' => 'valid-key']);
        $phdStudent1 = Student::factory()->create(['type' => Student::TYPE_PHD]);
        $phdStudent2 = Student::factory()->create(['type' => Student::TYPE_PHD]);
        $postgradProjectStudent = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $invactivePhdStudent = Student::factory()->create(['is_active' => false, 'type' => Student::TYPE_PHD]);
        $meeting1 = Meeting::factory()->create(['meeting_at' => now()->subDays(10), 'student_id' => $phdStudent1->id, 'supervisor_id' => $phdStudent1->supervisor_id]);
        $meeting2 = Meeting::factory()->create(['meeting_at' => now()->subDays(15), 'student_id' => $phdStudent2->id, 'supervisor_id' => $phdStudent2->supervisor_id]);
        $meeting3 = Meeting::factory()->create(['meeting_at' => now()->subDays(20), 'student_id' => $postgradProjectStudent->id, 'supervisor_id' => $postgradProjectStudent->supervisor_id]);
        $inactivePhdMeeting = Meeting::factory()->create(['meeting_at' => now()->subDays(40), 'student_id' => $invactivePhdStudent->id, 'supervisor_id' => $invactivePhdStudent->supervisor_id]);

        $response = $this->getJson('/api/students/?type='.Student::TYPE_POSTGRAD_PROJECT, [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'last_meeting_at' => $meeting3->meeting_at->format('Y-m-d'),
            'student_id' => $meeting3->student_id,
            'student_name' => $meeting3->student->full_name,
            'student_username' => $meeting3->student->username,
            'student_email' => $meeting3->student->email,
            'supervisor_id' => $meeting3->supervisor_id,
            'supervisor_name' => $meeting3->supervisor->full_name,
            'supervisor_username' => $meeting3->supervisor->username,
            'supervisor_email' => $meeting3->supervisor->email,
        ]);
    }

    /** @test */
    public function we_can_get_a_supervisor_and_their_latest_meetings(): void
    {
        config(['tier4.api_key' => 'valid-key']);
        $supervisor1 = User::factory()->create();
        $supervisor2 = User::factory()->create();
        $phdStudent1 = Student::factory()->create(['type' => Student::TYPE_PHD, 'supervisor_id' => $supervisor1->id]);
        $phdStudent2 = Student::factory()->create(['type' => Student::TYPE_PHD, 'supervisor_id' => $supervisor2->id]);
        $postgradProjectStudent = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT, 'supervisor_id' => $supervisor1->id]);
        $inactivePhdStudent = Student::factory()->create(['is_active' => false, 'type' => Student::TYPE_PHD]);
        $meeting1 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(10),
            'student_id' => $phdStudent1->id,
            'supervisor_id' => $supervisor1->id,
        ]);
        $meeting2 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(15),
            'student_id' => $phdStudent2->id,
            'supervisor_id' => $supervisor2->id,
        ]);
        $meeting3 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(20),
            'student_id' => $postgradProjectStudent->id,
            'supervisor_id' => $supervisor1->id,
        ]);

        $response = $this->getJson('/api/supervisor/'.$supervisor1->username, [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonCount(2, 'data.students');
        $response->assertJsonFragment([
            'supervisor_id' => $meeting1->supervisor_id,
            'supervisor_name' => $meeting1->supervisor->full_name,
            'supervisor_username' => $meeting1->supervisor->username,
            'supervisor_email' => $meeting1->supervisor->email,
            'students' => [
                [
                    'last_meeting_at' => $meeting1->meeting_at->format('Y-m-d'),
                    'student_id' => $meeting1->student_id,
                    'student_name' => $meeting1->student->full_name,
                    'student_username' => $meeting1->student->username,
                    'student_email' => $meeting1->student->email,
                ],
                [
                    'last_meeting_at' => $meeting3->meeting_at->format('Y-m-d'),
                    'student_id' => $meeting3->student_id,
                    'student_name' => $meeting3->student->full_name,
                    'student_username' => $meeting3->student->username,
                    'student_email' => $meeting3->student->email,
                ],
            ],
        ]);
    }

    /** @test */
    public function we_can_get_all_supervisors_and_their_latest_meetings(): void
    {
        config(['tier4.api_key' => 'valid-key']);
        $supervisor1 = User::factory()->create(['surname' => 'aaaa']); // give them surnames as we orderBy surname and makes testing easier
        $supervisor2 = User::factory()->create(['surname' => 'bbbb']);
        $phdStudent1 = Student::factory()->create(['type' => Student::TYPE_PHD, 'supervisor_id' => $supervisor1->id]);
        $phdStudent2 = Student::factory()->create(['type' => Student::TYPE_PHD, 'supervisor_id' => $supervisor2->id]);
        $postgradProjectStudent = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT, 'supervisor_id' => $supervisor1->id]);
        $inactivePhdStudent = Student::factory()->create(['is_active' => false, 'type' => Student::TYPE_PHD, 'supervisor_id' => $supervisor1->id]);
        $meeting1 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(10),
            'student_id' => $phdStudent1->id,
            'supervisor_id' => $supervisor1->id,
        ]);
        $meeting2 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(15),
            'student_id' => $phdStudent2->id,
            'supervisor_id' => $supervisor2->id,
        ]);
        $meeting3 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(20),
            'student_id' => $postgradProjectStudent->id,
            'supervisor_id' => $supervisor1->id,
        ]);
        $meeting4 = Meeting::factory()->create([
            'meeting_at' => now()->subDays(25),
            'student_id' => $inactivePhdStudent->id,
            'supervisor_id' => $supervisor1->id,
        ]);

        $response = $this->getJson('/api/supervisors', [
            'Authorization' => 'Bearer valid-key',
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonCount(2, 'data.0.students');
        $response->assertJsonCount(1, 'data.1.students');
        $response->assertJsonFragment([
            'supervisor_id' => $meeting1->supervisor_id,
            'supervisor_name' => $meeting1->supervisor->full_name,
            'supervisor_username' => $meeting1->supervisor->username,
            'supervisor_email' => $meeting1->supervisor->email,
            'students' => [
                [
                    'last_meeting_at' => $meeting1->meeting_at->format('Y-m-d'),
                    'student_id' => $meeting1->student_id,
                    'student_name' => $meeting1->student->full_name,
                    'student_username' => $meeting1->student->username,
                    'student_email' => $meeting1->student->email,
                ],
                [
                    'last_meeting_at' => $meeting3->meeting_at->format('Y-m-d'),
                    'student_id' => $meeting3->student_id,
                    'student_name' => $meeting3->student->full_name,
                    'student_username' => $meeting3->student->username,
                    'student_email' => $meeting3->student->email,
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'supervisor_id' => $meeting2->supervisor_id,
            'supervisor_name' => $meeting2->supervisor->full_name,
            'supervisor_username' => $meeting2->supervisor->username,
            'supervisor_email' => $meeting2->supervisor->email,
            'students' => [
                [
                    'last_meeting_at' => $meeting2->meeting_at->format('Y-m-d'),
                    'student_id' => $meeting2->student_id,
                    'student_name' => $meeting2->student->full_name,
                    'student_username' => $meeting2->student->username,
                    'student_email' => $meeting2->student->email,
                ],
            ],
        ]);
    }
}
