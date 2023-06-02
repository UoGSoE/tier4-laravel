<?php

namespace App\Console\Commands;

use App\Events\SomethingHappened;
use App\Mail\StaffOverdueMeeting;
use App\Models\User;
use Illuminate\Console\Command;
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
        $phdAdmins = User::active()->admin()->wantsPhdEmails()->get();
        $postgradProjectAdmins = User::active()->admin()->wantsPostgradProjectEmails()->get();
        $staffList = User::active()->with('students.latestMeeting')->get();

        $staffList->each(function ($staffMember) use ($phdAdmins, $postgradProjectAdmins) {
            $overdueStudents = $staffMember->students
                ->filter(fn ($student) => $student->isActive() && $student->isntSilenced())
                ->filter(fn ($student) => $student->isOverdue())
                ->filter(fn ($student) => $student->hasntBeenAlertedAboutRecently());

            if ($overdueStudents->count() == 0) {
                return;
            }
            $bccAddresses = collect([]);
            if ($overdueStudents->contains(fn ($student) => $student->isPhdStudent())) {
                $bccAddresses = $bccAddresses->merge($phdAdmins->pluck('email'));
            }
            if ($overdueStudents->contains(fn ($student) => $student->isPostgradProjectStudent())) {
                $bccAddresses = $bccAddresses->merge($postgradProjectAdmins->pluck('email'));
            }

            Mail::to($staffMember)->bcc($bccAddresses->unique())->later(now()->addSeconds(rand(10, 15 * 60)), new StaffOverdueMeeting($overdueStudents));

            $overdueStudents->each(fn ($student) => $student->updateAlertedAbout());

            event(new SomethingHappened("Email sent to {$staffMember->email} about overdue meetings for {$overdueStudents->pluck('email')->implode(', ')}"));
        });

        return Command::SUCCESS;
    }
}
