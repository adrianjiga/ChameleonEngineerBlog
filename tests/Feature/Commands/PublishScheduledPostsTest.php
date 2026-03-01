<?php

namespace Tests\Feature\Commands;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublishScheduledPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_draft_posts_whose_scheduled_at_has_passed(): void
    {
        $post = Post::factory()->scheduled()->create([
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
        $this->assertNotNull($post->fresh()->published_at);
    }

    public function test_sets_published_at_to_now_when_publishing(): void
    {
        $post = Post::factory()->scheduled()->create([
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->travelTo(now());

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $post->fresh()->published_at->timestamp,
            5
        );
    }

    public function test_does_not_publish_posts_scheduled_in_the_future(): void
    {
        $post = Post::factory()->scheduled()->create([
            'scheduled_at' => now()->addHour(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(PostStatus::Draft, $post->fresh()->status);
    }

    public function test_does_not_affect_already_published_posts(): void
    {
        $post = Post::factory()->published()->create();
        $originalPublishedAt = $post->published_at;

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
        $this->assertEquals($originalPublishedAt, $post->fresh()->published_at);
    }

    public function test_publishes_multiple_ready_posts_at_once(): void
    {
        Post::factory()->count(3)->scheduled()->create([
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(3, Post::where('status', PostStatus::Published)->count());
    }

    public function test_outputs_count_of_published_posts(): void
    {
        Post::factory()->count(2)->scheduled()->create([
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('Published 2 post(s).')
            ->assertSuccessful();
    }

    public function test_outputs_message_when_no_posts_are_ready(): void
    {
        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('No scheduled posts are ready to publish.')
            ->assertSuccessful();
    }

    public function test_publish_command_increments_cache_version_when_posts_are_published(): void
    {
        Cache::spy();

        Post::factory()->count(2)->scheduled()->create([
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        Cache::shouldHaveReceived('increment')->with('blog:index:version')->atLeast()->once();
    }

    public function test_publish_command_does_not_touch_cache_when_nothing_published(): void
    {
        Cache::spy();

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        Cache::shouldNotHaveReceived('increment');
    }

    public function test_publish_command_handles_100_posts_without_timeout(): void
    {
        Post::factory()->count(100)->scheduled()->create([
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(100, Post::where('status', \App\Enums\PostStatus::Published)->count());
    }
}
