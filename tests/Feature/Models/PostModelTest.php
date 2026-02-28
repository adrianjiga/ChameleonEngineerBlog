<?php

namespace Tests\Feature\Models;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makePost(array $attributes = []): Post
    {
        return Post::create(array_merge([
            'user_id' => $this->makeUser()->id,
            'title' => 'Test Post',
            'content' => 'Some content here.',
            'status' => PostStatus::Draft,
        ], $attributes));
    }

    public function test_auto_generates_slug_from_title(): void
    {
        $post = $this->makePost(['title' => 'Hello World']);

        $this->assertSame('hello-world', $post->slug);
    }

    public function test_does_not_overwrite_explicit_slug(): void
    {
        $post = $this->makePost(['title' => 'Hello World', 'slug' => 'custom-slug']);

        $this->assertSame('custom-slug', $post->slug);
    }

    public function test_updates_slug_when_title_changes(): void
    {
        $post = $this->makePost(['title' => 'Original Title']);

        $post->update(['title' => 'New Title']);

        $this->assertSame('new-title', $post->fresh()->slug);
    }

    public function test_does_not_update_slug_when_slug_explicitly_changed(): void
    {
        $post = $this->makePost(['title' => 'Original Title']);

        $post->update(['title' => 'New Title', 'slug' => 'locked-slug']);

        $this->assertSame('locked-slug', $post->fresh()->slug);
    }

    public function test_status_is_cast_to_post_status_enum(): void
    {
        $post = $this->makePost(['status' => PostStatus::Published]);

        $this->assertInstanceOf(PostStatus::class, $post->fresh()->status);
        $this->assertSame(PostStatus::Published, $post->fresh()->status);
    }

    public function test_route_key_name_is_slug(): void
    {
        $post = new Post;

        $this->assertSame('slug', $post->getRouteKeyName());
    }

    public function test_belongs_to_user(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost(['user_id' => $user->id]);

        $this->assertTrue($post->user->is($user));
    }

    public function test_belongs_to_many_categories(): void
    {
        $post = $this->makePost();
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech']);
        $post->categories()->attach($category);

        $this->assertTrue($post->categories->contains($category));
    }

    public function test_scope_published_returns_only_published_posts(): void
    {
        $this->makePost(['title' => 'Draft Post', 'status' => PostStatus::Draft]);
        $this->makePost(['title' => 'Published Post', 'slug' => 'published-post', 'status' => PostStatus::Published]);

        $results = Post::published()->get();

        $this->assertCount(1, $results);
        $this->assertSame('published-post', $results->first()->slug);
    }

    public function test_scope_scheduled_returns_draft_posts_with_scheduled_at(): void
    {
        $this->makePost(['title' => 'Plain Draft', 'status' => PostStatus::Draft]);
        $this->makePost(['title' => 'Scheduled', 'slug' => 'scheduled', 'status' => PostStatus::Draft, 'scheduled_at' => now()->addDay()]);

        $results = Post::scheduled()->get();

        $this->assertCount(1, $results);
        $this->assertSame('scheduled', $results->first()->slug);
    }

    public function test_scope_ready_to_publish_returns_past_scheduled_drafts(): void
    {
        $this->makePost(['title' => 'Future', 'status' => PostStatus::Draft, 'scheduled_at' => now()->addDay()]);
        $this->makePost(['title' => 'Past', 'slug' => 'past', 'status' => PostStatus::Draft, 'scheduled_at' => now()->subMinute()]);

        $results = Post::readyToPublish()->get();

        $this->assertCount(1, $results);
        $this->assertSame('past', $results->first()->slug);
    }

    public function test_scope_for_user_returns_posts_for_given_user(): void
    {
        $user = $this->makeUser();
        $this->makePost(['title' => 'Other Post']);
        $this->makePost(['title' => 'My Post', 'slug' => 'my-post', 'user_id' => $user->id]);

        $results = Post::forUser($user)->get();

        $this->assertCount(1, $results);
        $this->assertSame('my-post', $results->first()->slug);
    }

    public function test_scope_search_matches_title_and_excerpt(): void
    {
        $this->makePost(['title' => 'Laravel Tips', 'excerpt' => 'Something else']);
        $this->makePost(['title' => 'Something', 'slug' => 'something', 'excerpt' => 'About Laravel tricks']);
        $this->makePost(['title' => 'Unrelated', 'slug' => 'unrelated', 'excerpt' => null]);

        $results = Post::search('Laravel')->get();

        $this->assertCount(2, $results);
    }

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $post = $this->makePost(['content' => 'Short.']);

        $this->assertGreaterThanOrEqual(1, $post->reading_time);
    }

    public function test_reading_time_calculated_from_word_count(): void
    {
        $words = implode(' ', array_fill(0, 400, 'word'));
        $post = $this->makePost(['content' => $words]);

        $this->assertSame(2, $post->reading_time);
    }

    public function test_featured_image_urls_returns_empty_array_when_no_image(): void
    {
        $post = $this->makePost(['featured_image' => null]);

        $this->assertSame([], $post->featured_image_urls);
    }

    public function test_featured_image_urls_returns_all_variants_when_image_set(): void
    {
        $post = $this->makePost(['featured_image' => 'posts/image.webp']);

        $urls = $post->featured_image_urls;

        $this->assertSame('posts/image.webp', $urls['original']);
        $this->assertSame('posts/image_large.webp', $urls['large']);
        $this->assertSame('posts/image_medium.webp', $urls['medium']);
        $this->assertSame('posts/image_thumb.webp', $urls['thumb']);
    }
}
