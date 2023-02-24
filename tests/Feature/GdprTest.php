<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admins_can_export_all_data_about_a_student_as_a_json_file()
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $meeting1 = Meeting::factory()->create([
            'student_id' => $student->id,
        ]);
        $meeting2 = Meeting::factory()->create([
            'student_id' => $student->id,
        ]);
        $note1 = StudentNote::factory()->create([
            'student_id' => $student->id,
        ]);
        $note2 = StudentNote::factory()->create([
            'student_id' => $student->id,
        ]);
        $randomStudentNote = StudentNote::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.gdpr.student.export', $student));

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'name' => $student->full_name,
                'email' => $student->email,
                'meetings' => [
                    [
                        'date' => $meeting1->meeting_at->format('Y-m-d'),
                        'with' => $meeting1->supervisor->full_name,
                    ],
                    [
                        'date' => $meeting2->meeting_at->format('Y-m-d'),
                        'with' => $meeting2->supervisor->full_name,
                    ],
                ],
                'notes' => [
                    [
                        'date' => $note1->created_at->format('Y-m-d'),
                        'note' => $note1->body,
                        'by' => $note1->user->full_name,
                    ],
                    [
                        'date' => $note2->created_at->format('Y-m-d'),
                        'note' => $note2->body,
                        'by' => $note2->user->full_name,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function admins_can_export_all_data_about_a_staffmember_as_a_json_file()
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $meeting1 = Meeting::factory()->create([
            'supervisor_id' => $staff->id,
        ]);
        $meeting2 = Meeting::factory()->create([
            'supervisor_id' => $staff->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.gdpr.staff.export', $staff));

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'name' => $staff->full_name,
                'email' => $staff->email,
                'meetings' => [
                    [
                        'date' => $meeting1->meeting_at->format('Y-m-d'),
                        'with' => $meeting1->student->full_name,
                    ],
                    [
                        'date' => $meeting2->meeting_at->format('Y-m-d'),
                        'with' => $meeting2->student->full_name,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function regular_users_cant_do_gdpr_exports()
    {
        $student = Student::factory()->create();
        $staff = User::factory()->create();
        $otherStaff = User::factory()->create();

        $this->actingAs($staff)->get(route('admin.gdpr.student.export', $student))->assertUnauthorized();
        $this->actingAs($otherStaff)->get(route('admin.gdpr.staff.export', $staff))->assertUnauthorized();
    }
}
