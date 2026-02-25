<?php

namespace Tests\Feature\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_state_creates_a_draft_post(): void
    {
        $post = Post::factory()->create();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertNull($post->scheduled_at);
        $this->assertNull($post->featured_image);
    }

    public function test_default_state_creates_a_user_relationship(): void
    {
        $post = Post::factory()->create();

        $this->assertInstanceOf(User::class, $post->user);
    }

    public function test_published_state_sets_status_and_published_at(): void
    {
        $post = Post::factory()->published()->create();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertNull($post->scheduled_at);
    }

    public function test_draft_state_creates_a_plain_draft(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertNull($post->scheduled_at);
    }

    public function test_scheduled_state_sets_scheduled_at(): void
    {
        $post = Post::factory()->scheduled()->create();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNotNull($post->scheduled_at);
        $this->assertNull($post->published_at);
        $this->assertTrue($post->scheduled_at->isFuture());
    }

    public function test_with_featured_image_state_sets_featured_image(): void
    {
        $post = Post::factory()->withFeaturedImage()->create();

        $this->assertNotNull($post->featured_image);
        $this->assertStringStartsWith('posts/', $post->featured_image);
        $this->assertStringEndsWith('.webp', $post->featured_image);
    }

    public function test_states_can_be_combined(): void
    {
        $post = Post::factory()->published()->withFeaturedImage()->create();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->featured_image);
    }

    public function test_can_create_multiple_posts_for_a_user(): void
    {
        $user = User::factory()->create();
        Post::factory(3)->for($user)->create();

        $this->assertCount(3, $user->posts);
    }

    public function test_admin_state_on_user_factory(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
    }
}
