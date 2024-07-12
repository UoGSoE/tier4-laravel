<?php

namespace App\Console\Commands;

use App\Events\SomethingHappened;
use App\Mail\ProjectStudentReminder;
use App\Mail\StaffOverdueMeeting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotifyStaffOverdueMeetings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tier4:notify-staff-overdue-meetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify staff of overdue meetings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $staffList = User::active()->with('students.latestMeeting')->get();
        $this->handlePhdStudents($staffList);
        $this->handleProjectStudents($staffList);

        return Command::SUCCESS;
    }

    public function handlePhdStudents(Collection $staffList): void
    {
        $phdAdmins = User::active()->admin()->wantsPhdEmails()->get();

        $staffList->each(function ($staffMember) use ($phdAdmins) {
            $overdueStudents = $staffMember->students
                ->filter(fn ($student) => $student->isPhdStudent())
                ->filter(fn ($student) => $student->isActive() && $student->isntSilenced())
                ->filter(fn ($student) => $student->isOverdue())
                ->filter(fn ($student) => $student->hasntBeenAlertedAboutRecently());

            if ($overdueStudents->count() == 0) {
                return;
            }

            $bccAddresses = $phdAdmins->pluck('email');

            Mail::to($staffMember)->bcc($bccAddresses->unique())->later(now()->addSeconds(rand(10, 15 * 60)), new StaffOverdueMeeting($overdueStudents));

            $overdueStudents->each(fn ($student) => $student->updateAlertedAbout());

            event(new SomethingHappened("Email sent to {$staffMember->email} about overdue PhD meetings for {$overdueStudents->pluck('email')->implode(', ')}"));
        });
    }

    public function handleProjectStudents(Collection $staffList): void
    {
        $today = now()->format('Y-m-d');
        $shouldSendToday = false;
        foreach (range(1, 5) as $i) {
            if (option("postgrad_project_email_date_{$i}") == $today) {
                $shouldSendToday = true;
            }
        }

        if (! $shouldSendToday) {
            return;
        }

        $postgradProjectAdmins = User::active()->admin()->wantsPostgradProjectEmails()->get();
        $staffList->each(function ($staffMember) use ($postgradProjectAdmins) {
            $projectStudents = $staffMember->students
                ->filter(fn ($student) => $student->isPostgradProjectStudent())
                ->filter(fn ($student) => $student->isActive() && $student->isntSilenced());

            if ($projectStudents->count() == 0) {
                return;
            }

            $bccAddresses = $postgradProjectAdmins->pluck('email');

            Mail::to($staffMember)->bcc($bccAddresses->unique())->later(now()->addSeconds(rand(10, 15 * 60)), new ProjectStudentReminder());

            $projectStudents->each(fn ($student) => $student->updateAlertedAbout());

            event(new SomethingHappened("Email sent to {$staffMember->email} about overdue project student meetings for {$projectStudents->pluck('email')->implode(', ')}"));
        });
    }
}
