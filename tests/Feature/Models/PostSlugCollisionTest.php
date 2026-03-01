<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSlugCollisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_unique_when_duplicate_title_created(): void
    {
        $user = User::factory()->create();

        Post::factory()->for($user)->create(['title' => 'Hello World']);
        Post::factory()->for($user)->create(['title' => 'Hello World']);

        $this->assertSame(1, Post::where('slug', 'hello-world')->count());
        $this->assertSame(1, Post::where('slug', 'hello-world-2')->count());
    }

    public function test_slug_collision_appends_numeric_suffix(): void
    {
        $user = User::factory()->create();

        $first = Post::factory()->for($user)->create(['title' => 'Hello World']);
        $second = Post::factory()->for($user)->create(['title' => 'Hello World']);

        $this->assertSame('hello-world', $first->fresh()->slug);
        $this->assertSame('hello-world-2', $second->fresh()->slug);
    }

    public function test_multiple_slug_collisions_increment_counter(): void
    {
        $user = User::factory()->create();

        Post::factory()->count(5)->for($user)->create(['title' => 'Laravel Tips']);

        $slugs = Post::pluck('slug');

        $this->assertSame(5, $slugs->unique()->count());
        $this->assertContains('laravel-tips', $slugs->all());
        $this->assertContains('laravel-tips-2', $slugs->all());
        $this->assertContains('laravel-tips-3', $slugs->all());
        $this->assertContains('laravel-tips-4', $slugs->all());
        $this->assertContains('laravel-tips-5', $slugs->all());
    }

    public function test_category_slug_collision_is_also_resolved(): void
    {
        Category::factory()->create(['name' => 'Technology']);
        Category::factory()->create(['name' => 'Technology']);

        $this->assertSame(1, Category::where('slug', 'technology')->count());
        $this->assertSame(1, Category::where('slug', 'technology-2')->count());
    }
}
