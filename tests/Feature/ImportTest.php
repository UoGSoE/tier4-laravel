<?php

namespace Tests\Feature;

use App\Jobs\ImportProjectStudents;
use App\Mail\ImportProjectStudentsComplete;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Ohffs\Ldap\LdapConnectionInterface;
use Ohffs\Ldap\LdapUser;
use Ohffs\SimpleSpout\ExcelSheet;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function regular_users_cant_see_the_import_phd_students_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.import.phds.create'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function admin_staff_can_see_the_import_phd_students_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.import.phds.create'));

        $response->assertOk();
        $response->assertViewIs('admin.import.phd');
    }

    /** @test */
    public function admin_staff_can_import_a_spreadsheet_of_phd_students_and_their_supervisors(): void
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
    public function the_email_columns_must_be_valid_email_addresses(): void
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
    public function admins_can_see_the_project_students_upload_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.import.project-students.create'));

        $response->assertOk();
        $response->assertSee('Import project students');
    }

    /** @test */
    public function non_admins_cant_see_the_project_students_upload_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.import.project-students.create'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function admins_can_upload_the_project_students_from_their_mycampus_export()
    {
        Queue::fake();
        $admin = User::factory()->admin()->create();

        $sheetFilename = (new ExcelSheet())->generate($this->getGoodProjectRows());

        $response = $this->actingAs($admin)->post(route('admin.import.project-students.store'), [
            'sheet' => new UploadedFile($sheetFilename, 'mycampus-project-students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        $response->assertRedirect(route('admin.import.project-students.create'));
        $response->assertSessionDoesntHaveErrors();
        Queue::assertPushed(\App\Jobs\ImportProjectStudents::class);
    }

    /** @test */
    public function the_import_project_students_job_does_the_right_stuff()
    {
        Mail::fake();
        $this->fakeLdapConnection();

        ImportProjectStudents::dispatch($this->getGoodProjectRows(), 'admin@example.com');

        $this->assertEquals(4, User::count());
        $this->assertEquals(6, Student::count());

        collect($this->getGoodProjectRows())->each(fn ($row) => $this->assertDatabaseHas('students', [
            'username' => $row[0].strtolower($row[1][0]),
            'surname' => $row[1],
            'forenames' => $row[2],
            'email' => strtolower($row[14]),
            'supervisor_id' => User::where('email', '=', strtolower(trim($row[4])))->first()->id,
            'type' => Student::TYPE_POSTGRAD_PROJECT,
            'sub_type' => str_contains($row[6], 'MSc') ? Student::SUB_TYPE_MSC : Student::SUB_TYPE_BMENG,
        ]));

        Mail::assertQueued(ImportProjectStudentsComplete::class, function ($mail) {
            return $mail->hasTo('admin@example.com') && count($mail->errors) == 0;
        });
    }

    /** @test */
    public function the_import_project_students_job_logs_errors_on_malformed_data()
    {
        Mail::fake();
        $this->fakeLdapConnection();

        ImportProjectStudents::dispatch($this->getBadProjectRows(), 'admin@example.com');

        $this->assertEquals(0, Student::count());

        Mail::assertQueued(ImportProjectStudentsComplete::class, function ($mail) {
            return $mail->hasTo('admin@example.com') && count($mail->errors) == 6 &&
                $mail->errors[0] == 'Row 2: The matric field is required.' &&
                $mail->errors[1] == 'Row 3: The supervisor email field must be a valid email address.' &&
                $mail->errors[2] == 'Row 4: The matric field must be an integer.' &&
                $mail->errors[3] == 'Row 5: The email field is required.' &&
                $mail->errors[4] == 'Row 6: The forenames field is required.' &&
                $mail->errors[5] == 'Row 7: The email field must be a valid email address.';
        });
    }

    /** @test */
    public function the_import_project_students_job_doesnt_duplicate_existing_records()
    {
        Mail::fake();
        $this->fakeLdapConnection();

        $jennySmith = Student::factory()->create([
            'username' => '2451294s',
            'surname' => 'Smith',
            'forenames' => 'Jenny',
            'email' => '2451294s@example.com',
            'supervisor_id' => null,
        ]);
        $lizTruss = Student::factory()->create([
            'username' => '2273665j',
            'surname' => 'Jones',
            'forenames' => 'Tom',
            'email' => '2273665j@example.com',
            'supervisor_id' => null,
        ]);
        $pennyLane = User::factory()->create(['email' => 'penny.lane@example.com']);

        ImportProjectStudents::dispatch($this->getGoodProjectRows(), 'admin@example.com');

        $this->assertEquals(6, Student::count());
        $this->assertEquals(4, User::count());
        $this->assertEquals(1, User::where('email', '=', 'penny.lane@example.com')->count());

        Mail::assertQueued(ImportProjectStudentsComplete::class, function ($mail) {
            return $mail->hasTo('admin@example.com') && count($mail->errors) == 0;
        });
    }

    protected function getGoodProjectRows(): array
    {
        return [
            // ["Matric","Surname","Forenames","Supervisor Name","Supervisor Email","Programme code","Programme Name","Whatever","Load","Status","Credits","Fully Reg","Acad Reg","Fin Reg","Email"],
            ['2273664', 'Smith', 'Jenny', 'Brian MacDunnie', 'fred.smith@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', '2451294s@example.com'],
            ['2273665', 'Jones', 'Tom', 'Sarah Johnson', 'penny.lane@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', '2273665j@example.com'],
            ['2273666', 'Brown', 'Charlie', 'John Smith', 'mario.cart@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'charlie@example.com'],
            ['2273667', 'Doe', 'Jane', 'Mary Johnson', 'julia.smith@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'jane@example.com'],
            ['2273668', 'Garcia', 'Maria', 'David Rodriguez', 'julia.smith@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'maria@example.com'],
            ['2273669', 'Kim', 'Jin', 'Soo Lee', 'fred.smith@example.com', 'H6N1-5200', 'Elec & Elec Eng & Mgt,MSc', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'jin@example.com'],
        ];
    }

    public function getBadProjectRows(): array
    {
        return [
            // header row - should _not_ be logged as an error
            ['Matric', 'Surname', 'Forenames', 'Supervisor Name', 'Supervisor Email', 'Programme code', 'Programme Name', 'Whatever', 'Load', 'Status', 'Credits', 'Fully Reg', 'Acad Reg', 'Fin Reg', 'Email'],
            // missing matric
            ['', 'Smith', 'Jenny', 'Brian MacDunnie', 'fred.smith@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'jenny@example.com'],
            // invalid supervisor email
            ['2273665', 'Jones', 'Tom', 'Sarah Johnson', 'sarah@', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'tom@example.com'],
            // invalid matric
            ['hello', 'Brown', 'Charlie', 'John Smith', 'penny.lane@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'charlie@example.com'],
            // missing student email
            ['2273667', 'Doe', 'Jane', 'Mary Johnson', 'fred.smith@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', ''],
            // missing forename
            ['2273668', 'Garcia', '', 'David Rodriguez', 'penny.lane@example.com', 'H6N1-5200', 'Bachelor of Engineering', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'maria@example.com'],
            // invalid student email
            ['2273669', 'Kim', 'Jin', 'Soo Lee', 'penny.lane@example.com', 'H6N1-5200', 'Elec & Elec Eng & Mgt,MSc', 'Enrollment', 'Full-Time', 'International', '180', 'Y', 'Y', 'Y', 'jin@'],
        ];
    }

    private function fakeLdapConnection()
    {
        $this->instance(
            LdapConnectionInterface::class,
            new ImportLdapConnectionFake()
        );
    }
}

class ImportLdapConnectionFake implements LdapConnectionInterface
{
    public function authenticate(string $username, string $password)
    {
        return false;
    }

    public function findUser(string $username)
    {
        return null;
    }

    public function findUserByEmail(string $email)
    {
        return match ($email) {
            'fred.smith@example.com' => new LdapUser([
                0 => [
                    'uid' => ['fred.smith'],
                    'mail' => ['fred.smith@example.com'],
                    'sn' => ['Smith'],
                    'givenname' => ['Fred'],
                    'telephonenumber' => ['1234567890'],
                ],
            ]),
            'penny.lane@example.com' => new LdapUser([
                0 => [
                    'uid' => ['penny.lane'],
                    'mail' => ['penny.lane@example.com'],
                    'sn' => ['Lane'],
                    'givenname' => ['Penny'],
                    'telephonenumber' => ['0987654321'],
                ],
            ]),
            'mario.cart@example.com' => new LdapUser([
                0 => [
                    'uid' => ['mario.cart'],
                    'mail' => ['mario.cart@example.com'],
                    'sn' => ['Cart'],
                    'givenname' => ['Mario'],
                    'telephonenumber' => ['1234567890'],
                ],
            ]),
            'julia.smith@example.com' => new LdapUser([
                0 => [
                    'uid' => ['julia.smith'],
                    'mail' => ['julia.smith@example.com'],
                    'sn' => ['Smith'],
                    'givenname' => ['Julia'],
                    'telephonenumber' => ['0987654321'],
                ],
            ]),
            default => null
        };
    }
}
