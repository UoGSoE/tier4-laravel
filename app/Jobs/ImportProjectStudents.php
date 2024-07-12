<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
                    'email' => $rowContents[14],
                    'supervisor_email' => $rowContents[4],
                    'supervisor_name' => $rowContents[3],
                    'sub_type' => $rowContents[6],
                ],
                [
                    'matric' => 'required|integer',
                    'surname' => ['required', 'string', 'regex:/[a-zA-Z]+/'],
                    'forenames' => ['required', 'string', 'regex:/[a-zA-Z]+/'],
                    'email' => 'required|email',
                    'supervisor_email' => 'required|email',
                    'supervisor_name' => 'required|string',
                    'sub_type' => 'required|string',
                ]
            );

            if ($rowNumber === 0 && $validator->fails()) {
                // assume the first row is a header row, so don't log an error on it
                continue;
            }

            if ($validator->fails()) {
                $errors[] = "Row {$excelRowNumber}: ".implode(', ', $validator->errors()->all());

                continue;
            }

            $validatedData = $validator->validated();

            $supervisor = User::where('email', '=', strtolower(trim($validatedData['supervisor_email'])))->first();
            if (! $supervisor) {
                $ldapUser = \Ohffs\Ldap\LdapFacade::findUserByEmail(strtolower(trim($validatedData['supervisor_email'])));
                if (! $ldapUser) {
                    $errors[] = "Row {$excelRowNumber}: Could not find supervisor with email {$validatedData['supervisor_email']}";

                    continue;
                }
                $supervisor = new User();
                $supervisor->username = $ldapUser->username;
                $supervisor->password = bcrypt(Str::random(64));
                $supervisor->email = strtolower(trim($validatedData['supervisor_email']));
                $nameParts = explode(' ', Str::squish($validatedData['supervisor_name']));
                $supervisor->surname = array_pop($nameParts);
                $supervisor->forenames = implode(' ', $nameParts);
                $supervisor->is_staff = true;
                $supervisor->save();
            }

            $email = strtolower(trim($validatedData['email']));
            $student = Student::where('email', '=', $email)->first();
            $subType = Student::SUB_TYPE_BMENG;
            if (preg_match('/msc/i', $validatedData['sub_type'])) {
                $subType = Student::SUB_TYPE_MSC;
            }
            if (! $student) {
                $student = new Student();
                $student->email = $email;
                $student->username = $validatedData['matric'].strtolower($validatedData['surname'][0]);
                $student->forenames = $validatedData['forenames'];
                $student->surname = $validatedData['surname'];
                $student->type = Student::TYPE_POSTGRAD_PROJECT;
                $student->sub_type = $subType;
                $student->save();
            }
            $student->supervisor_id = $supervisor->id;
            $student->save();
        }

        Mail::to($this->userEmail)->queue(new \App\Mail\ImportProjectStudentsComplete($errors));
    }
}
// let's work this out in a step by step way to be sure we have the right answer
