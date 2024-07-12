<?php

namespace Tests\Feature;

use App\Events\SomethingHappened;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function we_can_manually_record_an_activity_message()
    {
        Activity::create([
            'message' => 'A student was created',
        ]);

        $this->assertCount(1, Activity::all());
        $this->assertEquals('A student was created', Activity::first()->message);
    }

    /** @test */
    public function there_is_a_scheduled_artisan_command_which_trims_the_activity_table()
    {
        $this->assertCommandIsScheduled('tier4:trim-activity-table');
    }

    /** @test */
    public function the_scheduled_artisan_command_trims_the_activity_table()
    {
        config(['tier4.activity_table_trim_days' => 7]);
        Activity::factory()->count(10)->create(['created_at' => now()->subDays(10)]);
        Activity::factory()->count(7)->create(['created_at' => now()->subDays(5)]);

        $this->artisan('tier4:trim-activity-table');

        $this->assertCount(7, Activity::all());
    }

    /** @test */
    public function we_can_fire_an_event_which_will_cause_activity_to_be_logged()
    {
        $this->assertCount(0, Activity::all());

        SomethingHappened::dispatch('A student was created');

        $this->assertEquals(1, Activity::count());
        $this->assertEquals('A student was created', Activity::first()->message);

    }

    /** @test */
    public function there_is_a_page_where_admin_users_can_see_the_event_log()
    {
        $this->withoutExceptionHandling();
        $admin = User::factory()->admin()->create();
        Activity::factory()->count(10)->create();

        $response = $this->actingAs($admin)->get(route('admin.activity.index'));

        $response->assertOk()
            ->assertSee('Activity Log')
            ->assertViewHas('activity');

        Activity::all()->each(fn ($activity) => $response->assertSee($activity->message));
    }
}
