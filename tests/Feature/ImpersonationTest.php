<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_users_can_impersonate_members_of_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        auth()->login($admin);
        $this->get(route('impersonate', $user))
            ->assertRedirect(route('home'));

        $this->assertTrue(auth()->user()->is($user));
    }

    /** @test */
    public function regular_users_cant_impersonate_members_of_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        auth()->login($user);
        $this->get(route('impersonate', $otherUser))
            ->assertUnauthorized();

        $this->assertTrue(auth()->user()->is($user));
    }

    /** @test */
    public function admin_users_can_stop_impersonateing_members_of_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        auth()->login($admin);
        $admin->impersonate($user);

        $this->assertTrue(auth()->user()->is($user));

        $this->get(route('impersonate.leave'))
            ->assertRedirect(route('home'));

        $this->assertTrue(auth()->user()->is($admin));
    }
}
