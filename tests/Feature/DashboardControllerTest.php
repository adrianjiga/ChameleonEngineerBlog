<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_renders_dashboard_component(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard', false));
    }

    public function test_stats_include_total_post_count_for_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->for($user)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalPosts', 3)
            );
    }

    public function test_stats_count_only_own_posts_for_non_admin(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Post::factory()->count(2)->for($user)->create();
        Post::factory()->count(5)->for($other)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalPosts', 2)
            );
    }

    public function test_admin_sees_all_posts_in_stats(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        Post::factory()->count(3)->for($admin)->create();
        Post::factory()->count(4)->for($other)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalPosts', 7)
            );
    }

    public function test_stats_include_published_and_draft_counts(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create();
        Post::factory()->count(2)->for($user)->draft()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.publishedPosts', 1)
                ->where('stats.draftPosts', 2)
            );
    }

    public function test_stats_include_total_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(4)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalCategories', 4)
            );
    }

    public function test_recent_posts_are_limited_to_five(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(8)->for($user)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('recentPosts', 5)
            );
    }

    public function test_popular_categories_are_limited_to_five(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(8)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('popularCategories', 5)
            );
    }
}
