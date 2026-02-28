<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('appearance.edit'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_access_appearance_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('settings/Appearance', false));
    }
}
