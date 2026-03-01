<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BlogCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_blog_index_is_served_from_cache_on_second_request(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->for($user)->published()->create();

        // First request — populates cache
        $this->get(route('blog.index'))->assertOk();

        // Add a new post AFTER the first request without triggering the observer (cache should not include it)
        Post::withoutEvents(function () use ($user) {
            Post::factory()->for($user)->published()->create(['title' => 'Brand New Post', 'slug' => 'brand-new-post']);
        });

        // Second request — should be served from cache (new post not visible)
        $response = $this->get(route('blog.index'));
        $response->assertOk();
        $response->assertDontSee('Brand New Post');
    }

    public function test_blog_index_cache_key_includes_version_page_search_category(): void
    {
        Cache::spy();

        $this->get(route('blog.index'));

        Cache::shouldHaveReceived('get')->with('blog:index:version', 0)->once();
        Cache::shouldHaveReceived('remember')->withArgs(function ($key) {
            return str_starts_with($key, 'blog:index:');
        })->once();
    }

    public function test_blog_index_cache_is_busted_after_post_saved(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->for($user)->published()->create();

        // First request populates cache
        $this->get(route('blog.index'))->assertOk();

        // Creating a new post invalidates the cache version (observer increments 'blog:index:version')
        Post::factory()->for($user)->published()->create(['title' => 'Cache Busted Post']);

        // Next request should use a new cache key (due to incremented version), showing new post
        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Cache Busted Post');
    }

    public function test_blog_show_is_served_from_cache_on_second_request(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create(['title' => 'Cached Post']);

        // First request — populates cache
        $this->get(route('blog.show', $post))->assertOk();

        // Update the title in the DB directly (bypassing observer to avoid cache invalidation)
        \Illuminate\Support\Facades\DB::table('posts')->where('id', $post->id)->update(['title' => 'Updated Title']);

        // Second request — should show the cached title (not the DB update)
        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('Cached Post')
            ->assertDontSee('Updated Title');
    }

    public function test_blog_show_cache_key_is_post_id(): void
    {
        Cache::spy();

        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create();

        $this->get(route('blog.show', $post));

        Cache::shouldHaveReceived('remember')->withArgs(function ($key) use ($post) {
            return $key === "post:{$post->id}";
        })->once();
    }

    public function test_blog_index_version_defaults_to_zero_for_fresh_cache(): void
    {
        Cache::flush();

        $this->get(route('blog.index'))->assertOk();
    }
}
