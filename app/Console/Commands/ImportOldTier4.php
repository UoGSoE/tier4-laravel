<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportOldTier4 extends Command
{
    protected $oldUserIdMap = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tier4:importoldtier4';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from the old Tier4 system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->importOldUsers();
        $this->importOldStudents();
        $this->importOldMeetings();

        return Command::SUCCESS;
    }

    protected function importOldUsers()
    {
        $oldUsers = DB::connection('oldtier4')->table('tier4_supervisors')->get();
        foreach ($oldUsers as $oldUser) {
            $newUser = User::where('username', '=', $oldUser->guid)->first();
            if (! $newUser) {
                $newUser = new User();
            }
            $newUser->username = $oldUser->guid;
            $newUser->surname = $oldUser->surname;
            $newUser->forenames = $oldUser->forenames;
            $newUser->email = $oldUser->email;
            $newUser->is_admin = false;
            $newUser->is_staff = true;
            $newUser->is_active = (bool) $oldUser->current;
            $newUser->password = bcrypt(Str::random(64));
            if (in_array($newUser->username, ['sc280r', 'ts1083', 'js5835'])) {
                $this->info('Mangling email as has two different GUID: '.$oldUser->guid);
                $newUser->email = '2.'.$newUser->email;
            }
            $newUser->save();
            $this->oldUserIdMap[$oldUser->id] = ['id' => $newUser->id, 'username' => $newUser->username];
        }
        $this->info('Imported '.count($oldUsers).' supervisors');

        $oldUsers = DB::connection('oldtier4')->table('tier4_admins')->get();
        foreach ($oldUsers as $oldUser) {
            $newUser = User::where('username', '=', $oldUser->guid)->first();
            if (! $newUser) {
                $newUser = new User();
            }
            [$name, $domain] = explode('@', $oldUser->email);
            [$forenames, $surname] = explode('.', $name);
            $newUser->username = $oldUser->guid;
            $newUser->surname = ucfirst($surname);
            $newUser->forenames = ucfirst($forenames);
            $newUser->email = $oldUser->email;
            $newUser->is_admin = true;
            $newUser->is_staff = true;
            $newUser->password = bcrypt(Str::random(64));
            $newUser->save();
        }
        $this->info('Imported '.count($oldUsers).' admins');
    }

    protected function importOldStudents()
    {
        $oldStudents = DB::connection('oldtier4')->table('tier4_students')->get();
        foreach ($oldStudents as $oldStudent) {
            $shouldAddNotes = false;
            $username = strtolower(trim($oldStudent->matric.$oldStudent->surname[0]));
            $newStudent = Student::where('username', '=', $username)->first();
            if (! $newStudent) {
                $newStudent = new Student();
                $shouldAddNotes = true;
            }
            if (! str_contains($oldStudent->email, '@')) {
                $this->info("Mangling student {$oldStudent->matric} who has an email of {$oldStudent->email}");
                $oldStudent->email = $oldStudent->matric.strtolower($oldStudent->surname[0]).'@student.gla.ac.uk';
            }
            $newStudent->username = $username;
            $newStudent->surname = $oldStudent->surname;
            $newStudent->forenames = $oldStudent->forenames;
            $newStudent->email = $oldStudent->email;
            $newStudent->supervisor_id = Arr::get($this->oldUserIdMap, "{$oldStudent->supervisor_id}.id", null);
            $newStudent->is_active = (bool) $oldStudent->current;
            $newStudent->save();
            if ($oldStudent->notes && $shouldAddNotes) {
                $newStudent->notes()->create([
                    'body' => $oldStudent->notes,
                    'user_id' => null,
                ]);
            }
        }
        $this->info('Imported '.count($oldStudents).' students');
    }

    protected function importOldMeetings()
    {
        $oldMeetings = DB::connection('oldtier4')->table('tier4_meetings')->get();
        $previousMeetingDate = now();
        foreach ($oldMeetings as $oldMeeting) {
            try {
                $meetingDate = Carbon::parse($oldMeeting->meeting_date);
                if ($meetingDate->isBefore(now()->subYears(20))) {
                    $this->info("Mangling meeting date {$oldMeeting->meeting_date} for student {$oldMeeting->student_id} to be {$previousMeetingDate}");
                    $meetingDate = $previousMeetingDate;
                }
            } catch (\Exception $e) {
                $this->info("Mangling meeting date {$oldMeeting->meeting_date} for student {$oldMeeting->student_id} to be {$previousMeetingDate}");
                $meetingDate = $previousMeetingDate;
            }
            $newMeeting = new Meeting();
            $newMeeting->student_id = $oldMeeting->student_id;
            $newMeeting->supervisor_id = Arr::get($this->oldUserIdMap, "{$oldMeeting->supervisor_id}.id", null);
            $newMeeting->meeting_at = $meetingDate;
            $newMeeting->save();
            $previousMeetingDate = $meetingDate;
        }
        $this->info('Imported '.count($oldMeetings).' meetings');
    }
}
