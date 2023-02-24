<?php

namespace Tests\Feature;

use App\Exports\PhdStudents;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ohffs\SimpleSpout\ExcelSheet;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admins_can_export_a_list_of_phd_students_and_their_supervisors()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.export.phds'));

        $response->assertDownload('tier4-phd-students-'.now()->format('d-m-Y').'.xlsx');
    }

    /** @test */
    public function the_exported_spreadsheet_will_contain_the_correct_info()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create(['username' => 'abc1x']);
        $staff2 = User::factory()->create(['username' => 'def1y']);
        $student1 = Student::factory()->create(['username' => '1234567s', 'supervisor_id' => $staff1->id, 'surname' => 'Aaaaa']);
        $student2 = Student::factory()->create(['username' => '2234567s', 'supervisor_id' => $staff2->id, 'surname' => 'Bbbbb']);
        $student3 = Student::factory()->create(['username' => '3234567s', 'supervisor_id' => $staff1->id, 'surname' => 'Ccccc']);
        $staff1->meetings()->create([
            'student_id' => $student1->id,
            'meeting_at' => now()->subDays(5),
        ]);
        $staff1->meetings()->create([
            'student_id' => $student3->id,
            'meeting_at' => now()->subDays(10),
        ]);
        $staff2->meetings()->create([
            'student_id' => $student2->id,
            'meeting_at' => now()->subDays(15),
        ]);

        $sheet = (new PhdStudents())->export();
        $rows = (new ExcelSheet())->import($sheet);

        $this->assertEquals(4, count($rows)); // 3 students + 1 header row
        $this->assertEquals([
            'Student GUID',
            'Surname',
            'Forenames',
            'Email',
            'Supervisor GUID',
            'Supervisor Surname',
            'Supervisor Forenames',
            'Supervisor Email',
            'Last Meeting',
        ], $rows[0]);
        $this->assertEquals([
            '1234567s',
            $student1->surname,
            $student1->forenames,
            $student1->email,
            $staff1->username,
            $staff1->surname,
            $staff1->forenames,
            $staff1->email,
            now()->subDays(5)->format('d/m/Y'),
        ], $rows[1]);
        $this->assertEquals([
            '2234567s',
            $student2->surname,
            $student2->forenames,
            $student2->email,
            $staff2->username,
            $staff2->surname,
            $staff2->forenames,
            $staff2->email,
            now()->subDays(15)->format('d/m/Y'),
        ], $rows[2]);
        $this->assertEquals([
            '3234567s',
            $student3->surname,
            $student3->forenames,
            $student3->email,
            $staff1->username,
            $staff1->surname,
            $staff1->forenames,
            $staff1->email,
            now()->subDays(10)->format('d/m/Y'),
        ], $rows[3]);
    }
}
