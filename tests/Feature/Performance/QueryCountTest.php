<?php

namespace Tests\Feature\Performance;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->mock(ImageOptimizer::class);
    }

    public function test_blog_index_does_not_trigger_n_plus_1(): void
    {
        $user = User::factory()->create();
        $categories = Category::factory()->count(2)->create();

        Post::factory()->count(15)->for($user)->published()->create()
            ->each(fn ($post) => $post->categories()->attach($categories->random(2)));

        DB::enableQueryLog();
        $this->get(route('blog.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, $count, "Expected ≤5 queries for blog index, got {$count}");
    }

    public function test_blog_show_does_not_trigger_n_plus_1(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->for($user)->published()->create();
        $post->categories()->attach($category);

        $relatedPosts = Post::factory()->count(3)->for($user)->published()->create();
        foreach ($relatedPosts as $related) {
            $related->categories()->attach($category);
        }

        DB::enableQueryLog();
        $this->get(route('blog.show', $post))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(7, $count, "Expected ≤7 queries for blog show, got {$count}");
    }

    public function test_posts_index_does_not_trigger_n_plus_1(): void
    {
        $admin = User::factory()->admin()->create();
        $categories = Category::factory()->count(2)->create();

        Post::factory()->count(15)->for($admin)->create()
            ->each(fn ($post) => $post->categories()->attach($categories->random(2)));

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('posts.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, $count, "Expected ≤5 queries for posts index, got {$count}");
    }

    public function test_dashboard_does_not_trigger_n_plus_1(): void
    {
        $admin = User::factory()->admin()->create();
        $categories = Category::factory()->count(5)->create();

        Post::factory()->count(20)->for($admin)->create()
            ->each(fn ($post) => $post->categories()->attach($categories->random()));

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(8, $count, "Expected ≤8 queries for dashboard, got {$count}");
    }
}
