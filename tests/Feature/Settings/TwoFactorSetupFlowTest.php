<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSetupFlowTest extends TestCase
{
    use RefreshDatabase;

    private function enableTwoFactor(): void
    {
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);
    }

    public function test_user_can_enable_two_factor_authentication(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $this->enableTwoFactor();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_user_can_confirm_two_factor_with_valid_code(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $this->enableTwoFactor();

        $user = User::factory()->create();

        // Enable 2FA first
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'));

        $user->refresh();

        // Decrypt the secret and generate a real TOTP code
        $secret = decrypt($user->two_factor_secret);
        $google2fa = new Google2FA;
        $code = $google2fa->getCurrentOtp($secret);

        // Confirm 2FA with valid code
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_user_cannot_confirm_two_factor_with_invalid_code(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $this->enableTwoFactor();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'));

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors();
    }

    public function test_user_can_disable_two_factor_authentication(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $this->enableTwoFactor();

        $user = User::factory()->withTwoFactor()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('two-factor.disable'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_user_can_view_recovery_codes(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $this->enableTwoFactor();

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.recovery-codes'))
            ->assertSuccessful();

        $this->assertIsArray($response->json());
        $this->assertNotEmpty($response->json());
    }

    public function test_user_can_regenerate_recovery_codes(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $this->enableTwoFactor();

        $user = User::factory()->withTwoFactor()->create();

        // Capture current recovery codes
        $oldCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.regenerate-recovery-codes'))
            ->assertRedirect();

        $newCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

        $this->assertNotEquals($oldCodes, $newCodes);
    }

    public function test_disable_2fa_requires_password_confirmation(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        // No password_confirmed_at in session
        $this->actingAs($user)
            ->delete(route('two-factor.disable'))
            ->assertRedirect(route('password.confirm'));
    }
}
