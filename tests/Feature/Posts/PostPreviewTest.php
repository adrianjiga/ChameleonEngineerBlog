<?php

namespace Tests\Feature\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.preview', $post))
            ->assertRedirect(route('login'));
    }

    public function test_author_can_preview_their_own_draft_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['status' => PostStatus::Draft]);

        $this->actingAs($user)
            ->get(route('posts.preview', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('blog/Show')
                ->where('isPreview', true)
                ->where('post.id', $post->id)
            );
    }

    public function test_author_can_preview_their_own_published_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['status' => PostStatus::Published]);

        $this->actingAs($user)
            ->get(route('posts.preview', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('blog/Show')
                ->where('isPreview', true)
            );
    }

    public function test_admin_can_preview_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->for(User::factory()->create())->create(['status' => PostStatus::Draft]);

        $this->actingAs($admin)
            ->get(route('posts.preview', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('blog/Show')
                ->where('isPreview', true)
            );
    }

    public function test_other_user_cannot_preview_another_users_post(): void
    {
        $post = Post::factory()->for(User::factory()->create())->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('posts.preview', $post))
            ->assertForbidden();
    }

    public function test_preview_renders_related_published_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['status' => PostStatus::Draft]);
        Post::factory()->for(User::factory()->create())->create(['status' => PostStatus::Published]);

        $this->actingAs($user)
            ->get(route('posts.preview', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('relatedPosts')
            );
    }
}
