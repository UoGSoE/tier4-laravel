<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Ohffs\SimpleSpout\ExcelSheet;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function regular_users_cant_see_the_import_phd_students_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.import.phds.create'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function admin_staff_can_see_the_import_phd_students_page()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.import.phds.create'));

        $response->assertOk();
        $response->assertViewIs('admin.import.phd');
    }

    /** @test */
    public function admin_staff_can_import_a_spreadsheet_of_phd_students_and_their_supervisors()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create(['username' => 'abc1x']);

        $sheetData = [
            ['Matric', 'Surname', 'Forenames', 'email', 'Supervisor GUID', 'Supervisor Surname', 'Supervisor Forenames', 'Supervisor Email'],
            ['1234567', 'Smith', 'John', 'jsmith@example.com', 'abc1x', 'Hull', 'Rod', 'supervisor1@example.com'],
            ['2234567', 'Smythe', 'Jane', 'jsmythe@example.com', 'def1y', 'Derbyshire', 'Delia', 'supervisor2@example.com'],
        ];

        $sheetFilename = (new ExcelSheet())->generate($sheetData);

        $response = $this->actingAs($admin)->post(route('admin.import.phds.store'), [
            'sheet' => new UploadedFile($sheetFilename, 'phd-students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        $response->assertRedirect(route('admin.import.phds.create'));
        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionHas('success');
        $staff2 = User::where('username', '=', 'def1y')->firstOrFail();
        $this->assertEquals('def1y', $staff2->username);
        $this->assertEquals('Derbyshire', $staff2->surname);
        $this->assertEquals('Delia', $staff2->forenames);
        $this->assertEquals('supervisor2@example.com', $staff2->email);
        $this->assertDatabaseHas('students', [
            'username' => '1234567s',
            'surname' => 'Smith',
            'forenames' => 'John',
            'email' => 'jsmith@example.com',
            'supervisor_id' => $staff1->id,
        ]);
        $this->assertDatabaseHas('students', [
            'username' => '2234567s',
            'surname' => 'Smythe',
            'forenames' => 'Jane',
            'email' => 'jsmythe@example.com',
            'supervisor_id' => $staff2->id,
        ]);
    }

    /** @test */
    public function the_email_columns_must_be_valid_email_addresses()
    {
        $admin = User::factory()->admin()->create();
        $staff1 = User::factory()->create(['username' => 'abc1x']);

        $sheetData = [
            ['Matric', 'Surname', 'Forenames', 'email', 'Supervisor GUID', 'Supervisor Surname', 'Supervisor Forenames', 'Supervisor Email'],
            ['1234567', 'Smith', 'John', '', 'abc1x', 'Hull', 'Rod', ''],
            ['2234567', 'Smythe', 'Jane', 'example.com', 'def1y', 'Derbyshire', 'Delia', 'supervisor2@'],
        ];

        $sheetFilename = (new ExcelSheet())->generate($sheetData);

        $response = $this->actingAs($admin)->post(route('admin.import.phds.store'), [
            'sheet' => new UploadedFile($sheetFilename, 'phd-students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        $response->assertRedirect(route('admin.import.phds.create'));
        $response->assertSessionHasErrors('Row 2:');
        $response->assertSessionHasErrors('Row 3:');
        $this->assertEquals(0, Student::count());
        $this->assertEquals(2, User::count());
    }

    /** @test */
    public function there_is_a_scheduled_task_to_import_project_students_and_supervisors_from_the_project_db_api()
    {
        $this->assertCommandIsScheduled('tier4:import-project-students');

        config(['tier4.project_db_api_url' => 'https://example.com/api/students/postgrad']);
        Http::fake([
            config('tier4.project_db_api_url') => Http::response([
                'data' => [
                    [
                        'username' => '1234567s',
                        'surname' => 'Smith',
                        'forenames' => 'John',
                        'email' => 'jsmith@example.com',
                        'supervisor' => [
                            'username' => 'abc1x',
                            'surname' => 'Hull',
                            'forenames' => 'Rod',
                            'email' => 'rhull@example.com',
                        ],
                    ],
                    [
                        'username' => '2234567s',
                        'surname' => 'Smythe',
                        'forenames' => 'Jane',
                        'email' => 'jsmythe@example.com',
                        'supervisor' => [
                            'username' => 'def1y',
                            'surname' => 'Derbyshire',
                            'forenames' => 'Delia',
                            'email' => 'deliad@example.com',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('tier4:import-project-students')
            ->doesntExpectOutputToContain('Failed')
            ->expectsOutputToContain('2 student records')
            ->assertExitCode(0);

        $supervisor1 = User::where('username', '=', 'abc1x')->firstOrFail();
        $this->assertEquals('abc1x', $supervisor1->username);
        $this->assertEquals('Hull', $supervisor1->surname);
        $this->assertEquals('Rod', $supervisor1->forenames);
        $this->assertEquals('rhull@example.com', $supervisor1->email);
        $supervisor2 = User::where('username', '=', 'def1y')->firstOrFail();
        $this->assertEquals('def1y', $supervisor2->username);
        $this->assertEquals('Derbyshire', $supervisor2->surname);
        $this->assertEquals('Delia', $supervisor2->forenames);
        $this->assertEquals('deliad@example.com', $supervisor2->email);
        $this->assertDatabaseHas('students', [
            'username' => '1234567s',
            'surname' => 'Smith',
            'forenames' => 'John',
            'email' => 'jsmith@example.com',
            'type' => Student::TYPE_POSTGRAD_PROJECT,
            'supervisor_id' => $supervisor1->id,
        ]);
        $this->assertDatabaseHas('students', [
            'username' => '2234567s',
            'surname' => 'Smythe',
            'forenames' => 'Jane',
            'email' => 'jsmythe@example.com',
            'type' => Student::TYPE_POSTGRAD_PROJECT,
            'supervisor_id' => $supervisor2->id,
        ]);
    }

    /** @test */
    public function invalid_data_from_the_api_is_discarded()
    {
        $this->assertCommandIsScheduled('tier4:import-project-students');

        config(['tier4.project_db_api_url' => 'https://example.com/api/students/postgrad']);
        Http::fake([
            config('tier4.project_db_api_url') => Http::response([
                'data' => [
                    [
                        'username' => '1234567s',
                        'surname' => 'Smith',
                        'forenames' => 'John',
                        'email' => 'jsmith@',
                        'supervisor' => [
                            'username' => 'abc1x',
                            'surname' => 'Hull',
                            'forenames' => 'Rod',
                            'email' => 'rhull@example.com',
                        ],
                    ],
                    [
                        'username' => '2234567s',
                        'surname' => 'Smythe',
                        'forenames' => 'Jane',
                        'email' => 'jsmythe@example.com',
                        'supervisor' => [],
                    ],
                ],
            ]),
        ]);

        $this->artisan('tier4:import-project-students')
            ->assertExitCode(0)
            ->expectsOutputToContain('0 student records')
            ->expectsOutputToContain('Failed');

        $this->assertEquals(0, Student::count());
        $this->assertEquals(1, User::count());  // supervisor1 is created as it is valid even though student1 isn't, supervisor2 is not created
        $supervisor1 = User::where('username', '=', 'abc1x')->firstOrFail();
        $this->assertEquals('abc1x', $supervisor1->username);
        $this->assertEquals('Hull', $supervisor1->surname);
        $this->assertEquals('Rod', $supervisor1->forenames);
        $this->assertEquals('rhull@example.com', $supervisor1->email);
    }
}
