<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_index(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('categories/Index', false)
                ->has('categories', 3)
            );
    }

    public function test_index_includes_posts_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->for($user)->create()->categories()->attach($category);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.posts_count', 1)
            );
    }

    public function test_index_includes_can_flags_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create();

        $this->actingAs($admin)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.can.update', true)
                ->where('categories.0.can.delete', true)
                ->where('can.create', true)
            );
    }

    public function test_index_includes_can_flags_for_regular_user(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.can.update', false)
                ->where('categories.0.can.delete', false)
                ->where('can.create', false)
            );
    }

    // ── Store ──────────────────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Laravel Tips',
                'description' => 'Tips and tricks for Laravel.',
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Laravel Tips']);
    }

    public function test_store_redirects_with_success_flash(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => 'New Category'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('flash.success', 'Category created successfully.');
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Sneaky Category'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Sneaky Category']);
    }

    public function test_store_validates_required_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_unique_name(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Existing Category']);

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => 'Existing Category'])
            ->assertSessionHasErrors('name');
    }

    // ── Update ─────────────────────────────────────────────────────────────

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), ['name' => 'New Name'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_update_redirects_with_success_flash(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->put(route('categories.update', $category), ['name' => 'Updated Name'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('flash.success', 'Category updated successfully.');
    }

    public function test_non_admin_cannot_update_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Original Name']);

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'Hijacked Name'])
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Original Name']);
    }

    public function test_update_allows_same_name_for_same_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Same Name']);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), ['name' => 'Same Name'])
            ->assertRedirect(route('categories.index'));
    }

    public function test_update_validates_unique_name_against_other_categories(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Taken Name']);
        $category = Category::factory()->create(['name' => 'Other Name']);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), ['name' => 'Taken Name'])
            ->assertSessionHasErrors('name');
    }

    // ── Destroy ────────────────────────────────────────────────────────────

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_redirects_with_success_flash(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('flash.success', 'Category deleted successfully.');
    }

    public function test_non_admin_cannot_delete_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_guests_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('login'));
    }
}
