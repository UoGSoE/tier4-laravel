<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ImportProjectStudents implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $sheetData, public string $userEmail)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $errors = [];
        foreach ($this->sheetData as $rowNumber => $rowContents) {
            $excelRowNumber = $rowNumber + 1;

            $validator = Validator::make(
                [
                    'matric' => $rowContents[0],
                    'surname' => $rowContents[1],
                    'forenames' => $rowContents[2],
                    'email' => $rowContents[21],
                    'supervisor_email' => $rowContents[16],
                ],
                [
                    'matric' => 'required|integer',
                    'surname' => 'required|string',
                    'forenames' => 'required|string',
                    'email' => 'required|email',
                    'supervisor_email' => 'required|email',
                ]
            );

            if ($rowNumber === 0 && $validator->fails()) {
                // assume the first row is a header row, so don't log an error on it
                continue;
            }

            if ($validator->fails()) {
                $errors[] = "Row {$excelRowNumber}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            $supervisor = User::where('email', '=', strtolower(trim($rowContents[16])))->first();
            if (! $supervisor) {
                $username = \Ohffs\Ldap\LdapFacade::findUserByEmail(strtolower(trim($rowContents[16])))?->username;
                if (! $username) {
                    $errors[] = "Row {$excelRowNumber}: Could not find supervisor with email {$rowContents[16]}";
                    continue;
                }
                $supervisor = new User();
                $supervisor->username = $username;
                $supervisor->password = bcrypt(Str::random(64));
                $supervisor->email = strtolower(trim($rowContents[16]));
                $nameParts = explode(" ", Str::squish($rowContents[15]));
                $supervisor->surname = array_pop($nameParts);
                $supervisor->forenames = implode(" ", $nameParts);
                $supervisor->is_staff = true;
                $supervisor->wants_postgrad_project_emails = true;
                $supervisor->wants_phd_emails = true;
                $supervisor->save();
            }

            $email = strtolower(trim($rowContents[21]));
            $student = Student::where('email', '=', $email)->first();
            if (! $student) {
                $student = new Student();
                $student->email = $email;
                $student->username = $rowContents[0] . strtolower($rowContents[1][0]);
                $student->forenames = $rowContents[2];
                $student->surname = $rowContents[1];
                $student->save();
            }
            $student->supervisor_id = $supervisor->id;
            $student->save();
        }

        Mail::to($this->userEmail)->queue(new \App\Mail\ImportProjectStudentsComplete($errors));
    }
}
// let's work this out in a step by step way to be sure we have the right answer
