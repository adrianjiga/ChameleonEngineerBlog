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
}
