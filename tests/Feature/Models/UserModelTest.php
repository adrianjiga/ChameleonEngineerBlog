<?php

namespace Tests\Feature\Models;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_defaults_to_false(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->is_admin);
    }

    public function test_is_admin_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertIsBool($user->is_admin);
        $this->assertTrue($user->is_admin);
    }

    public function test_is_admin_returns_true_for_admin(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_has_many_posts(): void
    {
        $user = User::factory()->create();
        Post::create([
            'user_id' => $user->id,
            'title' => 'Post One',
            'content' => 'Content',
            'status' => PostStatus::Draft,
        ]);
        Post::create([
            'user_id' => $user->id,
            'title' => 'Post Two',
            'slug' => 'post-two',
            'content' => 'Content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertCount(2, $user->posts);
    }

    public function test_user_is_not_admin_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->is_admin);
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_factory_state_sets_is_admin_true(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->isAdmin());
    }

    public function test_password_is_hashed_not_stored_as_plaintext(): void
    {
        $user = User::factory()->create(['password' => 'plaintext-password']);

        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plaintext-password', $user->password));
    }

    public function test_two_factor_secret_is_hidden_in_json_serialization(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $json = json_decode($user->toJson(), true);

        $this->assertArrayNotHasKey('two_factor_secret', $json);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $json);
    }

    public function test_user_has_many_posts_relationship(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->for($user)->create();

        $this->assertSame(3, $user->posts()->count());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->posts());
    }
}
