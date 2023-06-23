<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Mail\StaffOverdueMeeting;
use App\Mail\ProjectStudentReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Console\Commands\NotifyStaffOverdueMeetings;

class StaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        option(['phd_meeting_reminder_days' => 28]);
        option(['postgrad_project_email_date_1' => now()->format('Y-m-d')]);
        option(['postgrad_project_email_date_2' => now()->addDays(1)->format('Y-m-d')]);
        option(['postgrad_project_email_date_3' => now()->addDays(2)->format('Y-m-d')]);
        option(['postgrad_project_email_date_4' => now()->addDays(3)->format('Y-m-d')]);
        option(['postgrad_project_email_date_5' => now()->addDays(4)->format('Y-m-d')]);
    }

    /** @test */
    public function the_command_to_notify_staff_is_registered_with_laravel()
    {
        $this->assertCommandIsScheduled(\App\Console\Commands\NotifyStaffOverdueMeetings::class);
    }

    /** @test */
    public function staff_are_notified_about_phd_students_they_havent_met_with_in_some_time(): void
    {
        Mail::fake();
        $staff = User::factory()->create();
        $staff2 = User::factory()->create();
        $admin1 = User::factory()->admin()->create(['wants_phd_emails' => true]);
        $admin2 = User::factory()->admin()->create(['wants_phd_emails' => false]);
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student4 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student5 = Student::factory()->create(['supervisor_id' => $staff2->id]);
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(10)]);
        $staff->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(30)]);
        $staff->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(50)]);
        $staff->meetings()->create(['student_id' => $student4->id, 'meeting_at' => now()->subDays(20)]);
        $staff2->meetings()->create(['student_id' => $student5->id, 'meeting_at' => now()->subDays(20)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 1);
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student2, $student3, $admin1) {
            return $mail->hasTo($staff->email) &&
                $mail->hasBcc($admin1->email) &&
                $mail->overdueStudents->count() == 2 &&
                $mail->overdueStudents->contains($student2) &&
                $mail->overdueStudents->contains($student3);
        });
    }

    /** @test */
    public function staff_are_not_notified_about_phd_students_marked_as_silenced(): void
    {
        Mail::fake();
        $staff = User::factory()->create();
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id, 'is_silenced' => false]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id, 'is_silenced' => true]);
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(30)]);
        $staff->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(30)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 1);
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student1) {
            return $mail->hasTo($staff->email) &&
                $mail->overdueStudents->count() == 1 &&
                $mail->overdueStudents->contains($student1);
        });
    }

    /** @test */
    public function staff_are_only_notified_about_phd_students_at_most_once_a_week()
    {
        Mail::fake();
        config(['tier4.days_between_renotifications' => 7]);
        $staff = User::factory()->create();
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student4 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student5 = Student::factory()->create(['supervisor_id' => $staff2->id]);
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(10)]);
        $staff->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(30)]);
        $staff->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(50)]);
        $staff->meetings()->create(['student_id' => $student4->id, 'meeting_at' => now()->subDays(20)]);
        $staff2->meetings()->create(['student_id' => $student5->id, 'meeting_at' => now()->subDays(20)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 1);
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student2, $student3) {
            return $mail->hasTo($staff->email) &&
                $mail->overdueStudents->count() == 2 &&
                $mail->overdueStudents->contains($student2) &&
                $mail->overdueStudents->contains($student3);
        });

        Mail::fake();

        $this->artisan('tier4:notify-staff-overdue-meetings');
        Mail::assertNothingQueued();

        $this->travel(8)->days();

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 1);
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student2, $student3) {
            return $mail->hasTo($staff->email) &&
                $mail->overdueStudents->count() == 2 &&
                $mail->overdueStudents->contains($student2) &&
                $mail->overdueStudents->contains($student3);
        });

    }

    /** @test */
    public function inactive_staff_are_not_notified(): void
    {
        Mail::fake();
        $staff = User::factory()->create(['is_active' => false]);
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student4 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student5 = Student::factory()->create(['supervisor_id' => $staff2->id]);
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(10)]);
        $staff->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(30)]);
        $staff->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(50)]);
        $staff->meetings()->create(['student_id' => $student4->id, 'meeting_at' => now()->subDays(20)]);
        $staff2->meetings()->create(['student_id' => $student5->id, 'meeting_at' => now()->subDays(20)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertNothingQueued(StaffOverdueMeeting::class);
    }

    /** @test */
    public function staff_arent_notified_about_inactive_students_they_havent_met_with_in_some_time(): void
    {
        Mail::fake();
        $staff = User::factory()->create();
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id, 'is_active' => false]);
        $student4 = Student::factory()->create(['supervisor_id' => $staff->id]);
        $student5 = Student::factory()->create(['supervisor_id' => $staff2->id]);
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(10)]);
        $staff->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(30)]);
        $staff->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(50)]);
        $staff->meetings()->create(['student_id' => $student4->id, 'meeting_at' => now()->subDays(20)]);
        $staff2->meetings()->create(['student_id' => $student5->id, 'meeting_at' => now()->subDays(20)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 1);
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student2) {
            return $mail->hasTo($staff->email) &&
                $mail->overdueStudents->count() == 1 &&
                $mail->overdueStudents->contains($student2);
        });
    }

    /** @test */
    public function staff_are_only_notified_about_msc_project_students_on_certain_dates(): void
    {
        Mail::fake();
        // note: fixing the date to 1st July 2020 so we don't hit a logic edge case where the year changes at the end of december/beginning of january
        // see Student::getPostgradStartNotificationDate() and Student::getPostgradEndNotificationDate()
        // not fixing those as it *should* never actually happen as the dates are for the summer recess
        $this->travelTo(Carbon::createFromFormat('Y-m-d', '2020-08-02'));
        option(['postgrad_project_email_date_1' => '2020-07-01']);
        option(['postgrad_project_email_date_2' => '2020-08-02']);
        option(['postgrad_project_email_date_3' => '2020-09-03']);
        option(['postgrad_project_email_date_4' => '2020-10-04']);
        option(['postgrad_project_email_date_5' => '2020-11-05']);
        $staff = User::factory()->create();
        $staff2 = User::factory()->create();
        $admin1 = User::factory()->admin()->create(['wants_postgrad_project_emails' => true]);
        $admin2 = User::factory()->admin()->create(['wants_postgrad_project_emails' => false]);
        $student1 = Student::factory()->postgradProject()->create(['supervisor_id' => $staff->id, 'username' => 'student1']);
        $student2 = Student::factory()->postgradProject()->create(['supervisor_id' => $staff2->id, 'username' => 'student2']);
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(10)]);
        $staff2->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(20)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(ProjectStudentReminder::class, 2);
        Mail::assertQueued(ProjectStudentReminder::class, function ($mail) use ($staff, $admin1) {
            return $mail->hasTo($staff->email) && $mail->hasBcc($admin1->email);
        });
        Mail::assertQueued(ProjectStudentReminder::class, function ($mail) use ($staff2) {
            return $mail->hasTo($staff2->email);
        });

        Mail::fake();
        $this->travelTo(Carbon::createFromFormat('Y-m-d', '2020-08-01'));
        // pretend we've never sent an alert about any of these students just to test that has no effect for project students
        $student1->update(['last_alerted_about' => null]);
        $student2->update(['last_alerted_about' => null]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertNothingQueued();
    }
}
