<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_success_prop_is_shared_when_set(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['flash.success' => 'Post created!'])
            ->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('flash.success', 'Post created!')
        );
    }

    public function test_flash_error_prop_is_shared_when_set(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['flash.error' => 'Something went wrong.'])
            ->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('flash.error', 'Something went wrong.')
        );
    }

    public function test_flash_props_are_null_when_not_set(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('flash.success', null)
            ->where('flash.error', null)
        );
    }
}
