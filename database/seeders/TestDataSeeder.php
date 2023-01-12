<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Meeting;
use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Database\Seeder;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::factory()->admin()->has(Student::factory()->count(rand(1, 15)))->create([
            'username' => 'admin',
            'password' => bcrypt('secret'),
        ]);
        $admin2 = User::factory()->admin()->create([
            'username' => 'admin2',
            'password' => bcrypt('secret'),
        ]);

        $supervisors = User::factory()->count(50)->has(Student::factory()->count(rand(1, 15)))->create();
        $supervisors->each(fn ($supervisor) => $supervisor->students->each(
            fn ($student) => Meeting::factory()->count(rand(1, 50))->create([
                'student_id' => $student->id,
                'supervisor_id' => $student->supervisor_id
            ])
        ));
        Student::inRandomOrder()->take(100)->get()->each(fn ($student) => StudentNote::factory(rand(1, 5))->create(['student_id' => $student->id, 'user_id' => $admin->id]));
    }
}
