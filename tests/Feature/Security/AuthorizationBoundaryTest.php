<?php

namespace Tests\Feature\Security;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(ImageOptimizer::class);
    }

    public function test_user_cannot_edit_another_users_post(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $postA = Post::factory()->for($userA)->create();

        $this->actingAs($userB)
            ->get(route('posts.edit', $postA))
            ->assertForbidden();
    }

    public function test_user_cannot_update_another_users_post(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $postA = Post::factory()->for($userA)->create(['title' => 'Original Title']);

        $this->actingAs($userB)
            ->put(route('posts.update', $postA), [
                'title' => 'Stolen Title',
                'content' => 'Content.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $postA->id, 'title' => 'Original Title']);
    }

    public function test_user_cannot_delete_another_users_post(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $postA = Post::factory()->for($userA)->create();

        $this->actingAs($userB)
            ->delete(route('posts.destroy', $postA))
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $postA->id]);
    }

    public function test_regular_user_cannot_create_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), [
                'name' => 'Test Category',
            ])
            ->assertForbidden();
    }

    public function test_regular_user_cannot_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $category = Category::factory()->create();

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unauthenticated_autosave_returns_redirect_not_json_error(): void
    {
        $post = Post::factory()->for(User::factory()->create())->create();

        $this->patch(route('posts.autosave', $post), [
            'title' => 'Auto Title',
            'content' => 'Content.',
        ])
            ->assertRedirect(route('login'));
    }

    public function test_verified_email_required_for_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_category_delete_cascades_pivot_but_preserves_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $posts = Post::factory()->count(3)->for($admin)->create();
        foreach ($posts as $post) {
            $post->categories()->attach($category);
        }

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        foreach ($posts as $post) {
            $this->assertDatabaseHas('posts', ['id' => $post->id]);
        }

        $this->assertDatabaseMissing('category_post', ['category_id' => $category->id]);
    }
}
