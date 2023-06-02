<?php

namespace Tests\Feature;

use App\Models\Student;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkEditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admins_can_see_the_bulk_edit_page_for_students_of_a_given_type()
    {
        $this->withoutExceptionHandling();
        $admin = User::factory()->admin()->create();
        $phdStudent1 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_PHD]);
        $phdStudent2 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_PHD]);
        $phdStudent3 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_PHD]);
        $projectStudent1 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $projectStudent2 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $projectStudent3 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);

        $response = $this->actingAs($admin)->get(route('admin.bulk-edit-students.edit', ['type' => Student::TYPE_PHD]));

        $response->assertOk();
        $response->assertSee($phdStudent1->username);
        $response->assertSee($phdStudent2->username);
        $response->assertSee($phdStudent3->username);
        $response->assertDontSee($projectStudent1->username);
        $response->assertDontSee($projectStudent2->username);
        $response->assertDontSee($projectStudent3->username);
    }

    /** @test */
    public function active_and_inactive_students_appear_on_the_bulk_edit_page()
    {
        $this->withoutExceptionHandling();
        $admin = User::factory()->admin()->create();
        $phdStudent1 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_PHD, 'is_active' => true]);
        $phdStudent2 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_PHD, 'is_active' => false]);
        $phdStudent3 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_PHD, 'is_active' => true]);
        $projectStudent1 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $projectStudent2 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $projectStudent3 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);

        $response = $this->actingAs($admin)->get(route('admin.bulk-edit-students.edit', ['type' => Student::TYPE_PHD]));

        $response->assertOk();
        $response->assertSee($phdStudent1->username);
        $response->assertSee($phdStudent2->username);
        $response->assertSee($phdStudent3->username);
        $response->assertDontSee($projectStudent1->username);
        $response->assertDontSee($projectStudent2->username);
        $response->assertDontSee($projectStudent3->username);
    }

    /** @test */
    public function only_students_with_meetings_in_the_past_six_months_appear_on_the_bulk_edit_page()
    {
        $this->withoutExceptionHandling();
        $admin = User::factory()->admin()->create();
        $phdStudent1 = Student::factory()->hasMeetings(1, ['meeting_at' => now()->subMonths(6)])->create(['type' => Student::TYPE_PHD, 'is_active' => true]);
        $phdStudent2 = Student::factory()->hasMeetings(1, ['meeting_at' => now()->subMonths(7)])->create(['type' => Student::TYPE_PHD, 'is_active' => false]);
        $phdStudent3 = Student::factory()->hasMeetings(1, ['meeting_at' => now()->subMonths(5)])->create(['type' => Student::TYPE_PHD, 'is_active' => true]);
        $projectStudent1 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $projectStudent2 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);
        $projectStudent3 = Student::factory()->hasMeetings(1, ['meeting_at' => now()])->create(['type' => Student::TYPE_POSTGRAD_PROJECT]);

        $response = $this->actingAs($admin)->get(route('admin.bulk-edit-students.edit', ['type' => Student::TYPE_PHD]));

        $response->assertOk();
        $response->assertSee($phdStudent1->username);
        $response->assertDontSee($phdStudent2->username);
        $response->assertSee($phdStudent3->username);
        $response->assertDontSee($projectStudent1->username);
        $response->assertDontSee($projectStudent2->username);
        $response->assertDontSee($projectStudent3->username);
    }

    /** @test */
    public function admins_can_bulk_edit_the_students_flags()
    {
        $this->withoutExceptionHandling();
        $admin = User::factory()->admin()->create();
        $phdStudent1 = Student::factory()->create(['type' => Student::TYPE_PHD, 'is_active' => true, 'is_silenced' => false]);
        $phdStudent2 = Student::factory()->create(['type' => Student::TYPE_PHD, 'is_active' => true, 'is_silenced' => false]);
        $phdStudent3 = Student::factory()->create(['type' => Student::TYPE_PHD, 'is_active' => true, 'is_silenced' => false]);
        $projectStudent1 = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT, 'is_active' => true, 'is_silenced' => false]);
        $projectStudent2 = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT, 'is_active' => true, 'is_silenced' => false]);
        $projectStudent3 = Student::factory()->create(['type' => Student::TYPE_POSTGRAD_PROJECT, 'is_active' => true, 'is_silenced' => false]);

        $response = $this->actingAs($admin)->post(route('admin.bulk-edit-students.update', ['type' => Student::TYPE_PHD]), [
            'is_active' => [
                $phdStudent1->id => false,
                $phdStudent2->id => false,
                $phdStudent3->id => false,
            ],
            'is_silenced' => [
                $phdStudent1->id => true,
                $phdStudent2->id => true,
                $phdStudent3->id => true,
            ],
        ]);

        $response->assertRedirect(route('admin.bulk-edit-students.edit', ['type' => Student::TYPE_PHD]));
        $this->assertFalse($phdStudent1->fresh()->is_active);
        $this->assertTrue($phdStudent1->fresh()->is_silenced);
        $this->assertFalse($phdStudent2->fresh()->is_active);
        $this->assertTrue($phdStudent2->fresh()->is_silenced);
        $this->assertFalse($phdStudent3->fresh()->is_active);
        $this->assertTrue($phdStudent3->fresh()->is_silenced);
    }
}
