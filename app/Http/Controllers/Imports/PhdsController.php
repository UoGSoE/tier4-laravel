<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Ohffs\SimpleSpout\ExcelSheet;

class ImportPhdStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rows;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param array $rows
     * @param User $user
     */
    public function __construct(array $rows, User $user)
    {
        $this->rows = $rows;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $errors = new MessageBag();

        foreach ($this->rows as $index => $row) {
            $matric = $row[0] ?? '';
            $surname = $row[1] ?? '';
            $forenames = $row[2] ?? '';
            $email = strtolower(trim($row[3] ?? ''));
            $supervisorUsername = $row[4] ?? '';
            $supervisorSurname = $row[5] ?? '';
            $supervisorForenames = $row[6] ?? '';
            $supervisorEmail = strtolower(trim($row[7] ?? ''));

            $validator = Validator::make([
                'matric' => $matric,
                'surname' => $surname,
                'forenames' => $forenames,
                'email' => $email,
                'supervisorUsername' => $supervisorUsername,
                'supervisorSurname' => $supervisorSurname,
                'supervisorForenames' => $supervisorForenames,
                'supervisorEmail' => $supervisorEmail,
            ], [
                'matric' => 'required|string',
                'surname' => 'required|string',
                'forenames' => 'required|string',
                'email' => 'required|email',
                'supervisorUsername' => 'required|string',
                'supervisorSurname' => 'required|string',
                'supervisorForenames' => 'required|string',
                'supervisorEmail' => 'required|email',
            ]);

            if ($validator->fails()) {
                if ($index === 0) {
                    continue;  // assume it's the header row
                }
                $errors->add('Row '.$index + 1 .':', implode(', ', $validator->errors()->all()));

                continue;
            }

            $supervisor = User::where('username', '=', $supervisorUsername)->first();
            if (! $supervisor) {
                $supervisor = User::create([
                    'username' => $supervisorUsername,
                    'surname' => $supervisorSurname,
                    'forenames' => $supervisorForenames,
                    'email' => $supervisorEmail,
                    'password' => bcrypt(Str::random(64)),
                    'is_staff' => true,
                ]);
            }

            $student = Student::where('email', '=', $email)->first();
            if (! $student) {
                $student = Student::make([
                    'username' => strtolower($matric.$surname[0]),
                    'surname' => $surname,
                    'forenames' => $forenames,
                    'email' => $email,
                    'type' => Student::TYPE_PHD,
                ]);
            }
            $student->supervisor_id = $supervisor->id;
            $student->save();
        }

        SomethingHappened::dispatch("{$this->user->full_name} ran a PhD students import");

        if ($errors->count() > 0) {
            // TODO: send email to admin with error details
        }
    }
}
