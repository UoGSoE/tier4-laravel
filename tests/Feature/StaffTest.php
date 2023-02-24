<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function staff_can_see_the_list_of_their_current_students()
    {
        $staff = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id, 'is_active' => false]);
        $student4 = Student::factory()->create(['surname' => 'Differentsupervisorstudent']);

        $response = $this->actingAs($staff)->get(route('home'));

        $response->assertOk();
        $response->assertSee($student1->surname);
        $response->assertSee($student2->surname);
        $response->assertDontSee($student3->surname);
        $response->assertDontSee($student4->surname);
    }

    /** @test */
    public function staff_can_update_the_last_date_they_met_a_student()
    {
        $staff = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);

        $response = $this->actingAs($staff)->post(route('meetings.store'), [
            'meetings' => [
                ['student_id' => $student1->id, 'date' => now()->format('d/m/Y')],
                ['student_id' => $student2->id, 'date' => now()->subWeeks(2)->format('d/m/Y')],
                ['student_id' => $student3->id, 'date' => now()->subWeeks(3)->format('d/m/Y')],
            ],
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success', 'Meetings updated successfully');
        $this->assertEquals(now()->format('Y-m-d'), $student1->fresh()->lastMeetingWith($staff)->meeting_at->format('Y-m-d'));
        $this->assertEquals(now()->subWeeks(2)->format('Y-m-d'), $student2->fresh()->lastMeetingWith($staff)->meeting_at->format('Y-m-d'));
        $this->assertEquals(now()->subWeeks(3)->format('Y-m-d'), $student3->fresh()->lastMeetingWith($staff)->meeting_at->format('Y-m-d'));
    }

    /** @test */
    public function staff_leave_a_students_date_blank_if_they_have_never_met()
    {
        $staff = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);

        $response = $this->actingAs($staff)->post(route('meetings.store'), [
            'meetings' => [
                ['student_id' => $student1->id, 'date' => now()->format('d/m/Y')],
                ['student_id' => $student2->id, 'date' => now()->subWeeks(2)->format('d/m/Y')],
                ['student_id' => $student3->id, 'date' => ''],
            ],
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success', 'Meetings updated successfully');
        $this->assertEquals(now()->format('Y-m-d'), $student1->fresh()->lastMeetingWith($staff)->meeting_at->format('Y-m-d'));
        $this->assertEquals(now()->subWeeks(2)->format('Y-m-d'), $student2->fresh()->lastMeetingWith($staff)->meeting_at->format('Y-m-d'));
        $this->assertNull($student3->fresh()->lastMeetingWith($staff));
    }

    /** @test */
    public function the_dates_must_be_in_the_correct_format()
    {
        $staff = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);

        $response = $this->actingAs($staff)->post(route('meetings.store'), [
            'meetings' => [
                ['student_id' => $student1->id, 'date' => now()->format('Y-m-d')],
                ['student_id' => $student2->id, 'date' => now()->subWeeks(2)->format('d/m/Y')],
                ['student_id' => $student3->id, 'date' => ''],
            ],
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors('meetings.0.date');
        $this->assertNull($student1->fresh()->lastMeetingWith($staff));
        $this->assertNull($student2->fresh()->lastMeetingWith($staff));
        $this->assertNull($student3->fresh()->lastMeetingWith($staff));
    }

    /** @test */
    public function staff_cant_update_the_last_date_they_met_someone_elses_student()
    {
        $staff = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student4 = Student::factory()->create(['surname' => 'Differentsupervisorstudent']);

        $response = $this->actingAs($staff)->post(route('meetings.store'), [
            'meetings' => [
                ['student_id' => $student1->id, 'date' => now()->format('d/m/Y')],
                ['student_id' => $student2->id, 'date' => now()->subWeeks(2)->format('d/m/Y')],
                ['student_id' => $student3->id, 'date' => now()->subWeeks(3)->format('d/m/Y')],
                ['student_id' => $student4->id, 'date' => now()->subWeeks(4)->format('d/m/Y')],
            ],
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors('meetings.3.student_id');
        $this->assertNull($student1->fresh()->lastMeetingWith($staff));
        $this->assertNull($student2->fresh()->lastMeetingWith($staff));
        $this->assertNull($student3->fresh()->lastMeetingWith($staff));
        $this->assertNull($student4->fresh()->lastMeetingWith($staff));
    }
}
