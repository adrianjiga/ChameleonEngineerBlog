<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard', false));
    }

    // ── Stats ──────────────────────────────────────────────────────────────

    public function test_stats_prop_is_present(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('stats'));
    }

    public function test_stats_count_only_own_posts_for_regular_user(): void
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

    public function test_stats_break_down_published_and_draft_counts(): void
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

    public function test_stats_include_total_categories_count(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(4)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalCategories', 4)
            );
    }

    // ── Recent Posts ───────────────────────────────────────────────────────

    public function test_recent_posts_prop_is_present(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('recentPosts'));
    }

    public function test_recent_posts_are_limited_to_five(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(8)->for($user)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('recentPosts', 5));
    }

    public function test_regular_user_only_sees_own_recent_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Post::factory()->count(2)->for($user)->create();
        Post::factory()->count(3)->for($other)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('recentPosts', 2));
    }

    public function test_admin_sees_all_recent_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        Post::factory()->count(2)->for($admin)->create();
        Post::factory()->count(2)->for($other)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('recentPosts', 4));
    }

    // ── Popular Categories ─────────────────────────────────────────────────

    public function test_popular_categories_prop_is_present(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('popularCategories'));
    }

    public function test_popular_categories_are_limited_to_five(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(8)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('popularCategories', 5));
    }

    public function test_popular_categories_are_ordered_by_posts_count_descending(): void
    {
        $user = User::factory()->admin()->create();
        [$cat1, $cat2, $cat3] = Category::factory()->count(3)->create()->all();

        // 5 posts → cat1, 2 posts → cat2, 8 posts → cat3
        Post::factory()->count(5)->for($user)->published()->create()
            ->each(fn ($p) => $p->categories()->attach($cat1));
        Post::factory()->count(2)->for($user)->published()->create()
            ->each(fn ($p) => $p->categories()->attach($cat2));
        Post::factory()->count(8)->for($user)->published()->create()
            ->each(fn ($p) => $p->categories()->attach($cat3));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('popularCategories.0.name', $cat3->name)
                ->where('popularCategories.1.name', $cat1->name)
            );
    }

    public function test_stats_counts_scheduled_posts_as_draft(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->scheduled()->create(['scheduled_at' => now()->addDay()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.draftPosts', 1)
                ->where('stats.publishedPosts', 0)
            );
    }

    public function test_dashboard_stats_are_all_zero_when_no_posts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalPosts', 0)
                ->where('stats.publishedPosts', 0)
                ->where('stats.draftPosts', 0)
            );
    }

    public function test_recent_posts_are_ordered_by_created_at_descending(): void
    {
        $user = User::factory()->create();

        $oldest = Post::factory()->for($user)->create(['created_at' => now()->subDays(3), 'title' => 'Oldest Post']);
        $middle = Post::factory()->for($user)->create(['created_at' => now()->subDays(2), 'title' => 'Middle Post']);
        $newest = Post::factory()->for($user)->create(['created_at' => now()->subDay(), 'title' => 'Newest Post']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentPosts.0.title', 'Newest Post')
            );
    }
}
