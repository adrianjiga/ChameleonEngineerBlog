<?php

namespace Tests\Feature\Blog;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_page_2_returns_correct_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(20)->for($user)->published()->create();

        $this->get(route('blog.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 5)
                ->where('posts.current_page', 2)
            );
    }

    public function test_page_beyond_last_returns_empty_results(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(15)->for($user)->published()->create();

        $this->get(route('blog.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 0)
            );
    }

    public function test_search_combined_with_category_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Laravel']);

        $laravelTipsInCategory = Post::factory()->for($user)->published()->create(['title' => 'Laravel Tips']);
        $laravelTipsInCategory->categories()->attach($category);

        $laravelTricksNoCategory = Post::factory()->for($user)->published()->create(['title' => 'Laravel Tricks']);

        $vueTipsInCategory = Post::factory()->for($user)->published()->create(['title' => 'Vue Tips']);
        $vueTipsInCategory->categories()->attach($category);

        $this->get(route('blog.index', ['search' => 'Laravel', 'category' => $category->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $laravelTipsInCategory->id)
            );
    }

    public function test_search_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create(['title' => 'Laravel Performance Guide']);

        $this->get(route('blog.index', ['search' => 'laravel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }

    public function test_search_matches_excerpt_when_title_does_not_match(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create([
            'title' => 'Random Title',
            'excerpt' => 'This post covers Laravel best practices',
        ]);

        $this->get(route('blog.index', ['search' => 'laravel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }

    public function test_empty_search_returns_all_published_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(5)->for($user)->published()->create();
        Post::factory()->count(2)->for($user)->draft()->create();

        $this->get(route('blog.index', ['search' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 5));
    }

    public function test_unknown_category_slug_returns_empty_results(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(5)->for($user)->published()->create();

        $this->get(route('blog.index', ['category' => 'nonexistent-category']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
    }

    public function test_related_posts_capped_at_three(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $mainPost = Post::factory()->for($user)->published()->create();
        $mainPost->categories()->attach($category);

        $relatedPosts = Post::factory()->count(5)->for($user)->published()->create();
        foreach ($relatedPosts as $related) {
            $related->categories()->attach($category);
        }

        $this->get(route('blog.show', $mainPost))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('relatedPosts', 3));
    }

    public function test_related_posts_do_not_include_current_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $posts = Post::factory()->count(3)->for($user)->published()->create();
        foreach ($posts as $post) {
            $post->categories()->attach($category);
        }

        $firstPost = $posts->first();

        $this->get(route('blog.show', $firstPost))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('relatedPosts', fn ($related) => collect($related)->pluck('id')->doesntContain($firstPost->id))
            );
    }

    public function test_related_posts_empty_when_no_shared_category(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create();

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('relatedPosts', 0));
    }
}
