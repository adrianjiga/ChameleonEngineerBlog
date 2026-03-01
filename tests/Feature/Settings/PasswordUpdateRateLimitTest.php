<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordUpdateRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_update_is_rate_limited_after_six_attempts(): void
    {
        $user = User::factory()->create();

        // Pre-fill the throttle bucket to 5 (max is 6 per throttle:6,1)
        RateLimiter::increment(sha1($user->id), amount: 5);

        // 6th attempt — should succeed
        $this->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('user-password.edit'));

        // 7th attempt — should be rate limited
        $this->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'new-password-123',
                'password' => 'another-password',
                'password_confirmation' => 'another-password',
            ])
            ->assertTooManyRequests();
    }

    public function test_password_update_rate_limit_is_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Exhaust userA's bucket
        RateLimiter::increment(sha1($userA->id), amount: 6);

        // userB should still be able to update their password
        $this->actingAs($userB)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('user-password.edit'));
    }

    public function test_password_update_succeeds_within_rate_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user-password.edit'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }
}
