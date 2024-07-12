<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportOldTier4DataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeDatabase();
    }

    /** @test */
    public function we_can_import_the_old_tier4_data_into_the_system_via_an_artisan_command(): void
    {
        $this->assertDatabaseEmpty('users');
        $this->assertDatabaseEmpty('students');
        $this->assertDatabaseEmpty('meetings');
        $this->assertDatabaseEmpty('student_notes');

        $this->artisan('tier4:importoldtier4');

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('students', 3);
        $this->assertDatabaseCount('meetings', 4);
        $this->assertDatabaseCount('student_notes', 1);
        $this->assertDatabaseHas('users', [
            'username' => 'jsmith',
            'surname' => 'Smith',
            'forenames' => 'John',
            'email' => 'jsmith@example.com',
            'is_admin' => 0,
            'is_staff' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'qsmith',
            'surname' => 'Smith',
            'forenames' => 'Quincy',
            'email' => 'qsmith@example.com',
            'is_admin' => 0,
            'is_staff' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'zsmith',
            'surname' => 'Smith',
            'forenames' => 'Zippy',
            'email' => 'zsmith@example.com',
            'is_admin' => 0,
            'is_staff' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'ksmith',
            'surname' => 'Smith',
            'forenames' => 'Kelly',
            'email' => 'kelly.smith@example.com',
            'is_admin' => 1,
            'is_staff' => 1,
        ]);
        $this->assertDatabaseHas('students', [
            'forenames' => 'Jenny',
            'surname' => 'Smith',
            'email' => 'jennysmith@example.org',
            'username' => '1234567s',
            'supervisor_id' => User::where('username', '=', 'jsmith')->first()->id,
        ]);
        $this->assertDatabaseHas('students', [
            'forenames' => 'Nancy',
            'surname' => 'Smith',
            'email' => 'nancysmith@example.org',
            'username' => '2345678s',
            'supervisor_id' => User::where('username', '=', 'qsmith')->first()->id,
        ]);
        $this->assertDatabaseHas('students', [
            'forenames' => 'Sally',
            'surname' => 'Smith',
            'email' => 'sallysmith@example.org',
            'username' => '3456789s',
            'supervisor_id' => User::where('username', '=', 'qsmith')->first()->id,
        ]);
        tap(StudentNote::first(), function ($note) {
            $this->assertEquals('This is a note', $note->body);
            $this->assertEquals(Student::where('username', '=', '3456789s')->first()->id, $note->student_id);
            $this->assertNull($note->user_id);
        });

        $this->assertDatabaseHas('meetings', [
            'student_id' => Student::where('username', '=', '1234567s')->first()->id,
            'supervisor_id' => User::where('username', '=', 'jsmith')->first()->id,
            'meeting_at' => '2021-01-01',
        ]);
        $this->assertDatabaseHas('meetings', [
            'student_id' => Student::where('username', '=', '1234567s')->first()->id,
            'supervisor_id' => User::where('username', '=', 'jsmith')->first()->id,
            'meeting_at' => '2021-01-02',
        ]);
        $this->assertDatabaseHas('meetings', [
            'student_id' => Student::where('username', '=', '2345678s')->first()->id,
            'supervisor_id' => User::where('username', '=', 'qsmith')->first()->id,
            'meeting_at' => '2021-01-03',
        ]);
        $this->assertDatabaseHas('meetings', [
            'student_id' => Student::where('username', '=', '3456789s')->first()->id,
            'supervisor_id' => User::where('username', '=', 'qsmith')->first()->id,
            'meeting_at' => '2021-01-04',
        ]);
    }

    /** @test */
    public function importing_the_same_data_twice_doesnt_duplicate_it()
    {
        $this->artisan('tier4:importoldtier4');
        $this->artisan('tier4:importoldtier4');

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('students', 3);
        $this->assertDatabaseCount('meetings', 4);
        $this->assertDatabaseCount('student_notes', 1);
    }

    /** @test */
    public function importing_old_data_when_there_is_existing_data_doesnt_create_duplicates()
    {
        $existingStudent = Student::factory()->create([
            'username' => '1234567s',
            'forenames' => 'Existing',
            'surname' => 'Student',
            'email' => 'existing@example.com',
            'supervisor_id' => null,
        ]);
        $existingUser = User::factory()->create([
            'username' => 'jsmith',
            'surname' => 'Existing',
            'forenames' => 'User',
            'email' => 'existinguser@example.com',
        ]);
        $existingAdminUser = User::factory()->create([
            'username' => 'ksmith',
            'surname' => 'Existing',
            'forenames' => 'Admin',
        ]);

        $this->artisan('tier4:importoldtier4');

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('students', 3);
        $this->assertDatabaseCount('meetings', 4);
        $this->assertDatabaseCount('student_notes', 1);
    }

    protected function setUpFakeDatabase()
    {
        config([
            'database.connections.oldtier4' => [
                'driver' => 'sqlite',
                'url' => env('DATABASE_URL'),
                'database' => base_path().'/tests/fixtures/oldtier4.sqlite',
                'prefix' => '',
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ],
        ]);
        // deleting any old test data *carefully* rather than just truncating the tables
        // juuuust in case the code in here gets messed up and deletes the 'real' data
        DB::connection('oldtier4')->table('tier4_supervisors')->where('guid', '=', 'jsmith')->delete();
        DB::connection('oldtier4')->table('tier4_supervisors')->where('guid', '=', 'qsmith')->delete();
        DB::connection('oldtier4')->table('tier4_supervisors')->where('guid', '=', 'zsmith')->delete();
        DB::connection('oldtier4')->table('tier4_students')->where('matric', '=', '1234567')->delete();
        DB::connection('oldtier4')->table('tier4_students')->where('matric', '=', '2345678')->delete();
        DB::connection('oldtier4')->table('tier4_students')->where('matric', '=', '3456789')->delete();
        DB::connection('oldtier4')->table('tier4_admins')->where('guid', '=', 'ksmith')->delete();
        DB::connection('oldtier4')->table('tier4_meetings')->where('id', '=', '1')->delete();
        DB::connection('oldtier4')->table('tier4_meetings')->where('id', '=', '2')->delete();
        DB::connection('oldtier4')->table('tier4_meetings')->where('id', '=', '3')->delete();
        DB::connection('oldtier4')->table('tier4_meetings')->where('id', '=', '4')->delete();

        DB::connection('oldtier4')->table('tier4_supervisors')->insert([
            'id' => 1,
            'guid' => 'jsmith',
            'surname' => 'Smith',
            'forenames' => 'John',
            'email' => 'jsmith@example.com',
            'current' => 1,
        ]);
        DB::connection('oldtier4')->table('tier4_supervisors')->insert([
            'id' => 2,
            'guid' => 'qsmith',
            'surname' => 'Smith',
            'forenames' => 'Quincy',
            'email' => 'qsmith@example.com',
            'current' => 1,
        ]);
        DB::connection('oldtier4')->table('tier4_supervisors')->insert([
            'id' => 3,
            'guid' => 'zsmith',
            'surname' => 'Smith',
            'forenames' => 'Zippy',
            'email' => 'zsmith@example.com',
            'current' => 0,
        ]);
        DB::connection('oldtier4')->table('tier4_admins')->insert([
            'id' => 1,
            'guid' => 'ksmith',
            'email' => 'kelly.smith@example.com',
        ]);

        DB::connection('oldtier4')->table('tier4_students')->insert([
            'id' => 1,
            'forenames' => 'Jenny',
            'surname' => 'Smith',
            'email' => 'jennysmith@example.org',
            'matric' => '1234567',
            'supervisor_id' => 1, // jsmith
            'notes' => null,
        ]);
        DB::connection('oldtier4')->table('tier4_students')->insert([
            'id' => 2,
            'forenames' => 'Nancy',
            'surname' => 'Smith',
            'email' => 'nancysmith@example.org',
            'matric' => '2345678',
            'supervisor_id' => 2, // qsmith
            'notes' => null,
        ]);
        DB::connection('oldtier4')->table('tier4_students')->insert([
            'id' => 3,
            'forenames' => 'Sally',
            'surname' => 'Smith',
            'email' => 'sallysmith@example.org',
            'matric' => '3456789',
            'supervisor_id' => 2, // qsmith
            'notes' => 'This is a note',
        ]);
        DB::connection('oldtier4')->table('tier4_meetings')->insert([
            'id' => 1,
            'student_id' => 1,
            'supervisor_id' => 1,
            'meeting_date' => '2021-01-01 10:00:00',
        ]);
        DB::connection('oldtier4')->table('tier4_meetings')->insert([
            'id' => 2,
            'student_id' => 1,
            'supervisor_id' => 1,
            'meeting_date' => '2021-01-02 10:00:00',
        ]);
        DB::connection('oldtier4')->table('tier4_meetings')->insert([
            'id' => 3,
            'student_id' => 2,
            'supervisor_id' => 2,
            'meeting_date' => '2021-01-03 10:00:00',
        ]);
        DB::connection('oldtier4')->table('tier4_meetings')->insert([
            'id' => 4,
            'student_id' => 3,
            'supervisor_id' => 2,
            'meeting_date' => '2021-01-04 10:00:00',
        ]);
    }
}
