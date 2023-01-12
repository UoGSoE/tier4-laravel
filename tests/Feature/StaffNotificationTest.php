<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Mail\StaffOverdueMeeting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        option(['phd_meeting_reminder_days' => 28]);
        option(['postgrad_project_meeting_reminder_days' => 28]);
    }

    /** @test */
    public function staff_are_notified_about_students_they_havent_met_with_in_some_time()
    {
        Mail::fake();
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
    }

    /** @test */
    public function staff_are_not_notified_about_students_marked_as_silenced()
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
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student1, $student2) {
            return $mail->hasTo($staff->email) &&
                $mail->overdueStudents->count() == 1 &&
                $mail->overdueStudents->contains($student1);
        });
    }

    /** @test */
    public function inactive_staff_are_not_notified()
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
    public function staff_arent_notified_about_inactive_students_they_havent_met_with_in_some_time()
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
    public function staff_are_only_notified_about_msc_project_students_between_certain_dates()
    {
        Mail::fake();
        // note: fixing the date to 1st July 2020 so we don't hit a logic edge case where the year changes at the end of december/beginning of january
        // see Student::getPostgradStartNotificationDate() and Student::getPostgradEndNotificationDate()
        // not fixing those as it *should* never actually happen as the dates are for the summer recess
        $this->travelTo(Carbon::createFromFormat('Y-m-d', '2020-07-01'));
        option(['postgrad_project_start_day' => now()->subDays(10)->day]);
        option(['postgrad_project_start_month' => now()->subDays(10)->month]);
        option(['postgrad_project_end_day' => now()->addDays(10)->day]);
        option(['postgrad_project_end_month' => now()->addDays(10)->month]);
        $staff = User::factory()->create();
        $staff2 = User::factory()->create();
        $student1 = Student::factory()->create(['supervisor_id' => $staff->id, 'username' => 'student1']);
        $student2 = Student::factory()->create(['supervisor_id' => $staff->id, 'username' => 'student2']);
        $student3 = Student::factory()->create(['supervisor_id' => $staff->id, 'type' => Student::TYPE_POSTGRAD_PROJECT, 'username' => 'student3']);
        $student4 = Student::factory()->create(['supervisor_id' => $staff->id, 'username' => 'student4']);
        $student5 = Student::factory()->create(['supervisor_id' => $staff2->id, 'username' => 'student5']);
        // student1 has a recent meeting so should not cause a notification
        $staff->meetings()->create(['student_id' => $student1->id, 'meeting_at' => now()->subDays(10)]);
        // student2 has an old meeting so should cause a notification
        $staff->meetings()->create(['student_id' => $student2->id, 'meeting_at' => now()->subDays(30)]);
        // student3 has an old meeting and is a postgrad project student so should cause a notification as we're in the right time period
        $staff->meetings()->create(['student_id' => $student3->id, 'meeting_at' => now()->subDays(50)]);
        // student4 has a recent meeting so should not cause a notification
        $staff->meetings()->create(['student_id' => $student4->id, 'meeting_at' => now()->subDays(20)]);
        // student5 has a recent meeting so should not cause a notification
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
        option(['postgrad_project_start_day' => now()->addDays(10)->day]);
        option(['postgrad_project_start_month' => now()->addDays(10)->month]);
        option(['postgrad_project_end_day' => now()->addDays(20)->day]);
        option(['postgrad_project_end_month' => now()->addDays(20)->month]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 1);
        Mail::assertQueued(StaffOverdueMeeting::class, function ($mail) use ($staff, $student2) {
            return $mail->hasTo($staff->email) &&
                $mail->overdueStudents->count() == 1 &&
                $mail->overdueStudents->contains($student2);
        });
    }

    /** @test */
    public function admins_can_optionally_be_copied_in_to_the_staff_notifications()
    {
        Mail::fake();
        // note: fixing the date to 1st July 2020 so we don't hit a logic edge case where the year changes at the end of december/beginning of january
        // see Student::getPostgradStartNotificationDate() and Student::getPostgradEndNotificationDate()
        // not fixing those as it *should* never actually happen as the dates are for the summer recess
        $this->travelTo(Carbon::createFromFormat('Y-m-d', '2020-07-01'));
        option(['postgrad_project_start_day' => now()->subDays(10)->day]);
        option(['postgrad_project_start_month' => now()->subDays(10)->month]);
        option(['postgrad_project_end_day' => now()->addDays(10)->day]);
        option(['postgrad_project_end_month' => now()->addDays(10)->month]);
        $staff = User::factory()->create(['email' => 'staff1@example.com']);
        $staff2 = User::factory()->create(['email' => 'staff2@example.com']);
        $adminWhoOnlyGetsPhdEmails = User::factory()->admin()->create(['wants_phd_emails' => true, 'wants_postgrad_project_emails' => false, 'email' => 'admin1@example.com']);
        $adminWhoOnlyGetsPostgradProjectEmails = User::factory()->admin()->create(['wants_phd_emails' => false, 'wants_postgrad_project_emails' => true, 'email' => 'admin2@example.com']);
        $adminWhoGetsBothPhdAndPostgradProjectEmails = User::factory()->admin()->create(['wants_phd_emails' => true, 'wants_postgrad_project_emails' => true, 'email' => 'admin3@example.com']);
        $adminWhoGetsNoEmails = User::factory()->admin()->create(['wants_phd_emails' => false, 'wants_postgrad_project_emails' => false, 'email' => 'admin4@example.com']);
        $phdStudent1 = Student::factory()->create(['supervisor_id' => $staff->id, 'email' => 'phd1@example.com']);
        $phdStudent2 = Student::factory()->create(['supervisor_id' => $staff->id, 'email' => 'phd2@example.com']);
        $postgradProjectStudent1 = Student::factory()->create(['supervisor_id' => $staff2->id, 'type' => Student::TYPE_POSTGRAD_PROJECT, 'email' => 'project@example.com']);
        $phdStudent3 = Student::factory()->create(['supervisor_id' => $staff->id, 'email' => 'phd4@example.com']);
        $phdStudent4 = Student::factory()->create(['supervisor_id' => $staff2->id, 'email' => 'phd5@example.com']);
        $postgradProjectStudent2 = Student::factory()->create(['supervisor_id' => $staff2->id, 'type' => Student::TYPE_POSTGRAD_PROJECT, 'email' => 'project6@example.com']);
        $staff->meetings()->create(['student_id' => $phdStudent1->id, 'meeting_at' => now()->subDays(10)]);
        $staff->meetings()->create(['student_id' => $phdStudent2->id, 'meeting_at' => now()->subDays(30)]);
        $staff->meetings()->create(['student_id' => $phdStudent3->id, 'meeting_at' => now()->subDays(20)]);
        $staff2->meetings()->create(['student_id' => $postgradProjectStudent1->id, 'meeting_at' => now()->subDays(30)]);
        $staff2->meetings()->create(['student_id' => $postgradProjectStudent2->id, 'meeting_at' => now()->subDays(35)]);

        $this->artisan('tier4:notify-staff-overdue-meetings');

        Mail::assertQueued(StaffOverdueMeeting::class, 2);
        Mail::assertQueued(
            StaffOverdueMeeting::class,
            fn ($mail) => $mail->hasTo($staff->email) &&
                $mail->hasBcc($adminWhoOnlyGetsPhdEmails->email) &&
                ! $mail->hasBcc($adminWhoOnlyGetsPostgradProjectEmails->email) &&
                $mail->hasBcc($adminWhoGetsBothPhdAndPostgradProjectEmails->email) &&
                ! $mail->hasBcc($adminWhoGetsNoEmails->email) &&
                $mail->overdueStudents->count() == 1 &&
                $mail->overdueStudents->contains($phdStudent2)
        );
        Mail::assertQueued(
            StaffOverdueMeeting::class,
            fn ($mail) => $mail->hasTo($staff2->email) &&
                ! $mail->hasBcc($adminWhoOnlyGetsPhdEmails->email) &&
                $mail->hasBcc($adminWhoOnlyGetsPostgradProjectEmails->email) &&
                $mail->hasBcc($adminWhoGetsBothPhdAndPostgradProjectEmails->email) &&
                ! $mail->hasBcc($adminWhoGetsNoEmails->email) &&
                $mail->overdueStudents->count() == 2 &&
                $mail->overdueStudents->contains($postgradProjectStudent1) &&
                $mail->overdueStudents->contains($postgradProjectStudent2)
        );
    }
}
