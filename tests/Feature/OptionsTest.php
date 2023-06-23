<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        option(['phd_meeting_reminder_days' => 28]);
        option(['postgrad_project_email_date_1' => '2019-01-01']);
        option(['postgrad_project_email_date_2' => '2019-01-02']);
        option(['postgrad_project_email_date_3' => '2019-01-03']);
        option(['postgrad_project_email_date_4' => '2019-01-04']);
        option(['postgrad_project_email_date_5' => '2019-01-05']);
    }

    /** @test */
    public function regular_users_cant_see_the_options_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.options.edit'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function admin_users_can_see_the_options_page(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('admin.options.edit'));

        $response->assertOk();
        $response->assertSee('Edit Options');
    }

    /** @test */
    public function regular_users_cant_change_any_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.options.update'), [
            'postgrad_project_email_date_1' => '2022-01-01',
        ]);

        $response->assertUnauthorized();
        $this->assertEquals('2019-01-01', option('postgrad_project_email_date_1'));
    }

    /** @test */
    public function admins_can_change_various_options(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.options.update'), [
            'postgrad_project_email_date_1' => '01/01/2021',
            'postgrad_project_email_date_2' => '02/01/2021',
            'postgrad_project_email_date_3' => '03/01/2021',
            'postgrad_project_email_date_4' => '04/01/2021',
            'postgrad_project_email_date_5' => '05/01/2021',
            'phd_meeting_reminder_days' => 30,
        ]);

        $response->assertRedirect(route('admin.options.edit'));
        $response->assertSessionHas('success', 'Options updated');
        $this->assertEquals('2021-01-01', option('postgrad_project_email_date_1'));
        $this->assertEquals('2021-01-02', option('postgrad_project_email_date_2'));
        $this->assertEquals('2021-01-03', option('postgrad_project_email_date_3'));
        $this->assertEquals('2021-01-04', option('postgrad_project_email_date_4'));
        $this->assertEquals('2021-01-05', option('postgrad_project_email_date_5'));
        $this->assertEquals(30, option('phd_meeting_reminder_days'));
    }

    /** @test */
    public function project_email_dates_can_be_empty(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.options.update'), [
            'postgrad_project_email_date_1' => '',
            'postgrad_project_email_date_2' => '',
            'postgrad_project_email_date_3' => '',
            'postgrad_project_email_date_4' => '',
            'postgrad_project_email_date_5' => '',
            'phd_meeting_reminder_days' => 30,
        ]);

        $response->assertRedirect(route('admin.options.edit'));
        $response->assertSessionHas('success', 'Options updated');
        $this->assertEquals('', option('postgrad_project_email_date_1'));
        $this->assertEquals('', option('postgrad_project_email_date_2'));
        $this->assertEquals('', option('postgrad_project_email_date_3'));
        $this->assertEquals('', option('postgrad_project_email_date_4'));
        $this->assertEquals('', option('postgrad_project_email_date_5'));
        $this->assertEquals(30, option('phd_meeting_reminder_days'));
    }

    /** @test */
    public function admins_cant_change_various_options_to_garbage_values(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->from(route('admin.options.edit'))->post(route('admin.options.update'), [
            'phd_meeting_reminder_days' => 0,
            'postgrad_project_email_date_1' => 'blah',
            'postgrad_project_email_date_2' => 'blah',
            'postgrad_project_email_date_3' => 'blah',
            'postgrad_project_email_date_4' => 'blah',
            'postgrad_project_email_date_5' => 'blah',
        ]);

        $response->assertRedirect(route('admin.options.edit'));
        $response->assertSessionHasErrors([
            'phd_meeting_reminder_days' => 'The phd meeting reminder days field must be at least 1.',
            'postgrad_project_email_date_1' => 'The postgrad project email date 1 field must match the format d/m/Y.',
            'postgrad_project_email_date_2' => 'The postgrad project email date 2 field must match the format d/m/Y.',
            'postgrad_project_email_date_3' => 'The postgrad project email date 3 field must match the format d/m/Y.',
            'postgrad_project_email_date_4' => 'The postgrad project email date 4 field must match the format d/m/Y.',
            'postgrad_project_email_date_5' => 'The postgrad project email date 5 field must match the format d/m/Y.',
        ]);
        $this->assertEquals(28, option('phd_meeting_reminder_days'));
        $this->assertEquals('2019-01-01', option('postgrad_project_email_date_1'));
        $this->assertEquals('2019-01-02', option('postgrad_project_email_date_2'));
        $this->assertEquals('2019-01-03', option('postgrad_project_email_date_3'));
        $this->assertEquals('2019-01-04', option('postgrad_project_email_date_4'));
        $this->assertEquals('2019-01-05', option('postgrad_project_email_date_5'));
    }
}
