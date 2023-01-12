<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OptionsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        option(['postgrad_project_start_day' => 1]);
        option(['postgrad_project_start_month' => 5]);
        option(['postgrad_project_end_day' => 1]);
        option(['postgrad_project_end_month' => 9]);
        option(['phd_meeting_reminder_days' => 28]);
        option(['postgrad_project_meeting_reminder_days' => 28]);
    }

    /** @test */
    public function regular_users_cant_see_the_options_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.options.edit'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function admin_users_can_see_the_options_page()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('admin.options.edit'));

        $response->assertOk();
        $response->assertSee('Edit Options');
    }

    /** @test */
    public function regular_users_cant_change_any_options()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.options.update'), [
            'postgrad_project_start_day' => 2,
        ]);

        $response->assertUnauthorized();
        $this->assertEquals(1, option('postgrad_project_start_day'));
    }

    /** @test */
    public function admins_can_change_various_options()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.options.update'), [
            'postgrad_project_start_day' => 2,
            'postgrad_project_start_month' => 6,
            'postgrad_project_end_day' => 2,
            'postgrad_project_end_month' => 10,
            'phd_meeting_reminder_days' => 30,
            'postgrad_project_meeting_reminder_days' => 30,
        ]);

        $response->assertRedirect(route('admin.options.edit'));
        $response->assertSessionHas('success', 'Options updated');
        $this->assertEquals(2, option('postgrad_project_start_day'));
        $this->assertEquals(6, option('postgrad_project_start_month'));
        $this->assertEquals(2, option('postgrad_project_end_day'));
        $this->assertEquals(10, option('postgrad_project_end_month'));
        $this->assertEquals(30, option('phd_meeting_reminder_days'));
        $this->assertEquals(30, option('postgrad_project_meeting_reminder_days'));
    }

    /** @test */
    public function admins_cant_change_various_options_to_garbage_values()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->from(route('admin.options.edit'))->post(route('admin.options.update'), [
            'postgrad_project_start_day' => 'hello',
            'postgrad_project_start_month' => 13,
            'postgrad_project_end_day' => -1,
            'postgrad_project_end_month' => 'june',
            'phd_meeting_reminder_days' => 0,
        ]);

        $response->assertRedirect(route('admin.options.edit'));
        $response->assertSessionHasErrors([
            'postgrad_project_start_day' => 'The postgrad project start day must be an integer.',
            'postgrad_project_start_month' => 'The postgrad project start month must not be greater than 12.',
            'postgrad_project_end_day' => 'The postgrad project end day must be at least 1.',
            'postgrad_project_end_month' => 'The postgrad project end month must be an integer.',
            'phd_meeting_reminder_days' => 'The phd meeting reminder days must be at least 1.',
        ]);
        $this->assertEquals(1, option('postgrad_project_start_day'));
        $this->assertEquals(5, option('postgrad_project_start_month'));
        $this->assertEquals(1, option('postgrad_project_end_day'));
        $this->assertEquals(9, option('postgrad_project_end_month'));
        $this->assertEquals(28, option('phd_meeting_reminder_days'));
    }
}
