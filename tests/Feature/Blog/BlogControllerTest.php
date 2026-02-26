<?php

namespace Tests\Feature\Blog;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_index_is_publicly_accessible(): void
    {
        $this->get(route('blog.index'))->assertOk();
    }

    public function test_index_renders_blog_index_component(): void
    {
        $this->get(route('blog.index'))
            ->assertInertia(fn (Assert $page) => $page->component('blog/Index', false));
    }

    public function test_index_only_shows_published_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create(['title' => 'Published Post']);
        Post::factory()->for($user)->draft()->create(['title' => 'Draft Post']);

        $this->get(route('blog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Published Post')
            );
    }

    public function test_index_paginates_at_15_posts_per_page(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(20)->for($user)->published()->create();

        $this->get(route('blog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 15)
                ->where('posts.per_page', 15)
            );
    }

    public function test_index_filters_by_search(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create(['title' => 'Laravel Tips']);
        Post::factory()->for($user)->published()->create(['title' => 'Vue Tricks']);

        $this->get(route('blog.index', ['search' => 'Laravel']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Laravel Tips')
            );
    }

    public function test_index_filters_by_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $postInCategory = Post::factory()->for($user)->published()->create();
        $postInCategory->categories()->attach($category);
        Post::factory()->for($user)->published()->create();

        $this->get(route('blog.index', ['category' => $category->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $postInCategory->id)
            );
    }

    public function test_index_shares_filters_as_props(): void
    {
        $this->get(route('blog.index', ['search' => 'test', 'category' => 'laravel']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'test')
                ->where('filters.category', 'laravel')
            );
    }

    public function test_show_returns_published_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create();

        $this->get(route('blog.show', $post))->assertOk();
    }

    public function test_show_renders_blog_show_component(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create();

        $this->get(route('blog.show', $post))
            ->assertInertia(fn (Assert $page) => $page->component('blog/Show', false));
    }

    public function test_show_returns_404_for_draft_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->draft()->create();

        $this->get(route('blog.show', $post))->assertNotFound();
    }

    public function test_show_includes_related_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->for($user)->published()->create();
        $post->categories()->attach($category);

        $related = Post::factory()->count(2)->for($user)->published()->create();
        foreach ($related as $r) {
            $r->categories()->attach($category);
        }

        $this->get(route('blog.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->has('relatedPosts', 2)
            );
    }
}
