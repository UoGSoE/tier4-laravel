<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class ImportProjectStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tier4:import-project-students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import project students and supervisors from the project database API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // make an http json get call to the project database API, parse the resulting array of students and their supervisors
        // and create/update the corresponding users and students in the tier4 database

        $response = Http::get(config('tier4.project_db_api_url'));

        if ($response->failed()) {
            $this->error('Failed to import project students and supervisors from the project database API');
            return Command::FAILURE;
        }

        $errors = new MessageBag();
        $studentRecordsProcessed = 0;

        $response->collect('data')->each(function ($student, $index) use ($errors, &$studentRecordsProcessed) {
            $supervisor = $student['supervisor'] ?? [];

            $validator = Validator::make($supervisor, [
                'username' => 'required|string',
                'surname' => 'required|string',
                'forenames' => 'required|string',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                $errors->add("supervisor_{$index}", $validator->errors()->toJson()  . json_encode($supervisor));
                return;
            }

            $supervisorUser = User::updateOrCreate([
                'username' => $supervisor['username'],
            ], [
                'surname' => $supervisor['surname'],
                'forenames' => $supervisor['forenames'],
                'email' => $supervisor['email'],
                'is_staff' => true,
                'is_active' => true,
                'password' => bcrypt(Str::random(64)),
            ]);

            $validator = Validator::make($student, [
                'username' => 'required|string',
                'surname' => 'required|string',
                'forenames' => 'required|string',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                $errors->add(
                    "student_{$index}",
                    $validator->errors()->toJson() .
                    json_encode($student)
                );
                return;
            }

            $studentUser = Student::updateOrCreate([
                'username' => $student['username'],
            ], [
                'surname' => $student['surname'],
                'forenames' => $student['forenames'],
                'email' => $student['email'],
                'is_active' => true,
                'supervisor_id' => $supervisorUser->id,
                'type' => Student::TYPE_POSTGRAD_PROJECT,
            ]);

            $studentRecordsProcessed++;
        });

        if ($errors->count() > 0) {
            \Sentry\captureMessage(implode("\n", $errors->all()));
            $this->error('Failed to import some entries');
        }

        $this->info('Complete: succefully processed ' . $studentRecordsProcessed . ' student records');
        return Command::SUCCESS;
    }
}
