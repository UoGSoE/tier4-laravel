<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Ohffs\SimpleSpout\ExcelSheet;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        option(['phd_meeting_reminder_days' => 28]);
        option(['postgrad_project_meeting_reminder_days' => 28]);
    }

    /** @test */
    public function admins_can_see_a_list_of_overdue_phd_students()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();
        $overdueStudent1 = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $overdueStudent2 = Student::factory()->create(['supervisor_id' => $staff2->id]);
        $notOverdueStudent = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $overdueButInactiveStudent = Student::factory()->inactive()->create(['supervisor_id' => $staff1->id]);
        $projectStudent = Student::factory()->postgradProject()->create(['supervisor_id' => $staff1->id]);
        $staff1->meetings()->create(['student_id' => $overdueStudent1->id, 'meeting_at' => now()->subDays(30)]);
        $staff2->meetings()->create(['student_id' => $overdueStudent2->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $notOverdueStudent->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $overdueButInactiveStudent->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $projectStudent->id, 'meeting_at' => now()->subDays(30)]);

        $response = $this->actingAs($admin)->get(route('reports.overdue', ['type' => Student::TYPE_PHD]));

        $response->assertOk();
        $response->assertSee($overdueStudent1->email);
        $response->assertSee($overdueStudent2->email);
        $response->assertDontSee($notOverdueStudent->email);
        $response->assertDontSee($overdueButInactiveStudent->email);
        $response->assertDontSee($projectStudent->email);
    }

    /** @test */
    public function admins_can_see_a_list_of_overdue_postgrad_project_students()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();
        $overdueStudent1 = Student::factory()->postgradProject()->create(['supervisor_id' => $staff1->id]);
        $overdueStudent2 = Student::factory()->postgradProject()->create(['supervisor_id' => $staff2->id]);
        $notOverdueStudent = Student::factory()->postgradProject()->create(['supervisor_id' => $staff1->id]);
        $overdueButInactiveStudent = Student::factory()->postgradProject()->inactive()->create(['supervisor_id' => $staff1->id]);
        $phdStudent = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $staff1->meetings()->create(['student_id' => $overdueStudent1->id, 'meeting_at' => now()->subDays(30)]);
        $staff2->meetings()->create(['student_id' => $overdueStudent2->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $notOverdueStudent->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $overdueButInactiveStudent->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $phdStudent->id, 'meeting_at' => now()->subDays(30)]);

        $response = $this->actingAs($admin)->get(route('reports.overdue', ['type' => Student::TYPE_POSTGRAD_PROJECT]));

        $response->assertOk();
        $response->assertSeeLivewire('students-meetings-report');
        $response->assertSee($overdueStudent1->email);
        $response->assertSee($overdueStudent2->email);
        $response->assertDontSee($notOverdueStudent->email);
        $response->assertDontSee($overdueButInactiveStudent->email);
        $response->assertDontSee($phdStudent->email);
    }

    /** @test */
    public function admins_can_filter_the_list_of_students_in_various_ways()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create(['surname' => 'zzzzzzzzz']);
        $staff2 = User::factory()->create(['surname' => 'aaaaaaaaa']);
        $overdueStudent1 = Student::factory()->postgradProject()->create(['supervisor_id' => $staff1->id, 'surname' => 'qqqqqqq']);
        $overdueStudent2 = Student::factory()->postgradProject()->create(['supervisor_id' => $staff2->id, 'surname' => 'wwwwwwww']);
        $notOverdueStudent = Student::factory()->postgradProject()->create(['supervisor_id' => $staff1->id, 'surname' => 'eeeeeeee']);
        $overdueButInactiveStudent = Student::factory()->postgradProject()->inactive()->create(['supervisor_id' => $staff1->id, 'surname' => 'rrrrrrrr']);
        $phdStudent = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $staff1->meetings()->create(['student_id' => $overdueStudent1->id, 'meeting_at' => now()->subDays(30)]);
        $staff2->meetings()->create(['student_id' => $overdueStudent2->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $notOverdueStudent->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $overdueButInactiveStudent->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $phdStudent->id, 'meeting_at' => now()->subDays(30)]);

        Livewire::actingAs($admin)->test('students-meetings-report', ['type' => Student::TYPE_POSTGRAD_PROJECT])
            ->assertSee($overdueStudent1->email)
            ->assertSee($overdueStudent2->email)
            ->assertDontSee($notOverdueStudent->email)
            ->assertDontSee($overdueButInactiveStudent->email)
            ->assertDontSee($phdStudent->email)
            ->set('filter', 'qqqqqqq')
            ->assertSee($overdueStudent1->email)
            ->assertDontSee($overdueStudent2->email)
            ->assertDontSee($notOverdueStudent->email)
            ->assertDontSee($overdueButInactiveStudent->email)
            ->assertDontSee($phdStudent->email)
            ->set('filter', '')
            ->assertSee($overdueStudent1->email)
            ->assertSee($overdueStudent2->email)
            ->assertDontSee($notOverdueStudent->email)
            ->assertDontSee($overdueButInactiveStudent->email)
            ->assertDontSee($phdStudent->email)
            ->set('type', Student::TYPE_PHD)
            ->assertDontSee($overdueStudent1->email)
            ->assertDontSee($overdueStudent2->email)
            ->assertDontSee($notOverdueStudent->email)
            ->assertDontSee($overdueButInactiveStudent->email)
            ->assertSee($phdStudent->email)
            ->set('type', Student::TYPE_POSTGRAD_PROJECT)
            ->assertSee($overdueStudent1->email)
            ->assertSee($overdueStudent2->email)
            ->assertDontSee($notOverdueStudent->email)
            ->assertDontSee($overdueButInactiveStudent->email)
            ->assertDontSee($phdStudent->email)
            ->set('includeInactive', true)
            ->assertSee($overdueStudent1->email)
            ->assertSee($overdueStudent2->email)
            ->assertDontSee($notOverdueStudent->email)
            ->assertSee($overdueButInactiveStudent->email)
            ->assertDontSee($phdStudent->email)
            ->set('onlyOverdue', false)
            ->assertSee($overdueStudent1->email)
            ->assertSee($overdueStudent2->email)
            ->assertSee($notOverdueStudent->email)
            ->assertSee($overdueButInactiveStudent->email)
            ->assertDontSee($phdStudent->email)
            ;
    }

    /** @test */
    public function admins_can_see_a_list_of_all_meetings_for_a_given_student()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(90)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(75)]);

        $response = $this->actingAs($admin)->get(route('reports.student', ['student' => $student1]));

        $response->assertOk();
        $response->assertSeeLivewire('student-meetings-report');
        $response->assertSee($student1->email);
        $response->assertSee($staff1->full_name);
        $response->assertSee(now()->subDays(30)->format('d/m/Y'));
        $response->assertSee(now()->subDays(3)->format('d/m/Y'));
        $response->assertSee(now()->subDays(90)->format('d/m/Y'));
        $response->assertDontSee($student2->email);
        $response->assertDontSee(now()->subDays(75)->format('d/m/Y'));
    }

    /** @test */
    public function admins_can_see_a_list_of_all_meetings_for_a_given_supervisor()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff1->id]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(90)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(75)]);
        $staff2->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(95)]);

        $response = $this->actingAs($admin)->get(route('reports.supervisor', ['supervisor' => $staff1]));

        $response->assertOk();
        $response->assertSee($staff1->full_name);
        $response->assertSee($student1->full_name);
        $response->assertSee($student2->full_name);
        $response->assertSee(now()->subDays(30)->format('d/m/Y'));
        $response->assertSee(now()->subDays(3)->format('d/m/Y'));
        $response->assertSee(now()->subDays(90)->format('d/m/Y'));
        $response->assertSee(now()->subDays(75)->format('d/m/Y'));
        $response->assertDontSee($staff2->full_name);
        $response->assertDontSee(now()->subDays(95)->format('d/m/Y'));
    }

    /** @test */
    public function admins_can_see_a_list_of_all_the_latest_meetings_for_all_supervisors()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff1->id, 'surname' => 'student1']);
        $student2 = Student::factory()->create(['supervisor_id' => $staff1->id, 'surname' => 'student2']);
        $student3 = Student::factory()->create(['supervisor_id' => $staff2->id, 'surname' => 'student3']);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(90)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(75)]);
        $staff2->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(95)]);

        $response = $this->actingAs($admin)->get(route('reports.supervisors'));

        $response->assertOk();
        $response->assertSeeLivewire('supervisors-report');
        $response->assertSee($staff1->full_name);
        $response->assertSee($staff2->full_name);
        $response->assertSee($student1->full_name);
        $response->assertSee($student2->full_name);
        $response->assertSee($student3->full_name);
        $response->assertSee(now()->subDays(30)->format('d/m/Y'));
        $response->assertSee(now()->subDays(3)->format('d/m/Y'));
        $response->assertSee(now()->subDays(95)->format('d/m/Y'));
        $response->assertDontSee(now()->subDays(90)->format('d/m/Y'));
        $response->assertDontSee(now()->subDays(75)->format('d/m/Y'));
    }

    /** @test */
    public function admins_can_filter_the_list_of_supervisors_on_the_report()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create(['surname' => 'staff1']);
        $staff2 = User::factory()->create(['surname' => 'staff2']);
        $student1 = Student::factory()->create(['supervisor_id' => $staff1->id, 'surname' => 'student1']);
        $student2 = Student::factory()->create(['supervisor_id' => $staff1->id, 'surname' => 'student2']);
        $student3 = Student::factory()->create(['supervisor_id' => $staff2->id, 'surname' => 'student3']);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(30)]);
        $staff1->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(90)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(3)]);
        $staff1->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(75)]);
        $staff2->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(95)]);

        Livewire::actingAs($admin)->test('supervisors-report')
            ->assertSee($staff1->full_name)
            ->assertSee($staff2->full_name)
            ->assertSee($student1->full_name)
            ->assertSee($student2->full_name)
            ->assertSee($student3->full_name)
            ->set('filter', $staff1->surname)
            ->assertSee($staff1->full_name)
            ->assertDontSee($staff2->full_name)
            ->assertSee($student1->full_name)
            ->assertSee($student2->full_name)
            ->assertDontSee($student3->full_name)
            ->set('filter', '')
            ->assertSee($staff1->full_name)
            ->assertSee($staff2->full_name)
            ->assertSee($student1->full_name)
            ->assertSee($student2->full_name)
            ->assertSee($student3->full_name);
    }
}
