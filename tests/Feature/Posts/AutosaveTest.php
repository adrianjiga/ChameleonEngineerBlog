<?php

namespace Tests\Feature\Posts;

use App\Models\Post;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AutosaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(ImageOptimizer::class);
    }

    public function test_autosave_is_rate_limited_after_60_requests(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        // Pre-fill the bucket to 60 (max is 60 per throttle:60,1)
        RateLimiter::increment(sha1($user->id), amount: 60);

        $this->actingAs($user)
            ->patch(route('posts.autosave', $post), [
                'title' => 'Auto Saved Title',
                'content' => 'Some content.',
            ])
            ->assertTooManyRequests();
    }

    public function test_autosave_rate_limit_is_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post1 = Post::factory()->for($user1)->create();
        $post2 = Post::factory()->for($user2)->create();

        // Exhaust user1's rate limit bucket
        RateLimiter::increment(sha1($user1->id), amount: 60);

        // user2 should still be able to autosave their post
        $this->actingAs($user2)
            ->patch(route('posts.autosave', $post2), [
                'title' => 'Valid Title',
                'content' => 'Content.',
            ])
            ->assertOk();
    }

    public function test_autosave_does_not_redirect_on_success(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('posts.autosave', $post), [
                'title' => 'Auto Saved',
                'content' => 'Content.',
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);
    }

    public function test_autosave_with_empty_title_fails_validation(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson(route('posts.autosave', $post), [
                'title' => '',
                'content' => 'Content.',
            ])
            ->assertUnprocessable();
    }
}
