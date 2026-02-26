<?php

namespace Tests\Feature\Posts;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake();
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('posts.index'))->assertRedirect(route('login'));
    }

    public function test_user_sees_their_own_posts_on_index(): void
    {
        $user = User::factory()->create();
        $own = Post::factory()->for($user)->create(['title' => 'My Post']);
        Post::factory()->for(User::factory()->create())->create(['title' => 'Others Post']);

        $this->actingAs($user)
            ->get(route('posts.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'My Post')
            );
    }

    public function test_admin_sees_all_posts_on_index(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->for($admin)->create();
        Post::factory()->for(User::factory()->create())->create();

        $this->actingAs($admin)
            ->get(route('posts.index'))
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
    }

    public function test_create_page_is_accessible_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('posts.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('posts/Create', false));
    }

    public function test_store_creates_post_and_redirects(): void
    {
        $user = User::factory()->create();
        $this->mock(ImageOptimizer::class);

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'New Post',
                'content' => 'Some content.',
            ])
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', ['title' => 'New Post', 'user_id' => $user->id]);
    }

    public function test_store_uploads_featured_image_when_provided(): void
    {
        $user = User::factory()->create();

        $this->mock(ImageOptimizer::class, function (Mockery\MockInterface $mock) {
            $mock->shouldReceive('optimize')->once()->andReturn('posts/image.webp');
        });

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'Post with Image',
                'content' => 'Content.',
                'featured_image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', ['featured_image' => 'posts/image.webp']);
    }

    public function test_store_syncs_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $this->mock(ImageOptimizer::class);

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'Post with Category',
                'content' => 'Content.',
                'category_ids' => [$category->id],
            ])
            ->assertRedirect(route('posts.index'));

        $post = Post::where('title', 'Post with Category')->first();
        $this->assertTrue($post->categories->contains($category));
    }

    public function test_edit_page_is_accessible_to_post_owner(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('posts.edit', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('posts/Edit', false));
    }

    public function test_edit_page_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($other)->create();

        $this->actingAs($user)
            ->get(route('posts.edit', $post))
            ->assertForbidden();
    }

    public function test_update_modifies_post_and_redirects(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();
        $this->mock(ImageOptimizer::class);

        $this->actingAs($user)
            ->put(route('posts.update', $post), [
                'title' => 'Updated Title',
                'content' => 'Updated content.',
            ])
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Title']);
    }

    public function test_update_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($other)->create();
        $this->mock(ImageOptimizer::class);

        $this->actingAs($user)
            ->put(route('posts.update', $post), [
                'title' => 'Stolen Title',
                'content' => 'Content.',
            ])
            ->assertForbidden();
    }

    public function test_destroy_deletes_post_and_redirects(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($other)->create();

        $this->actingAs($user)
            ->delete(route('posts.destroy', $post))
            ->assertForbidden();
    }

    public function test_autosave_returns_json_saved_response(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();
        $this->mock(ImageOptimizer::class);

        $this->actingAs($user)
            ->patch(route('posts.autosave', $post), [
                'title' => 'Auto Saved',
                'content' => 'Content.',
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);
    }

    public function test_autosave_is_forbidden_for_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($other)->create();
        $this->mock(ImageOptimizer::class);

        $this->actingAs($user)
            ->patch(route('posts.autosave', $post), [
                'title' => 'Title',
                'content' => 'Content.',
            ])
            ->assertForbidden();
    }

    public function test_upload_image_returns_url_on_success(): void
    {
        $user = User::factory()->create();

        $this->mock(ImageOptimizer::class, function (Mockery\MockInterface $mock) {
            $mock->shouldReceive('optimize')->once()->andReturn('posts/uploaded.webp');
        });

        $this->actingAs($user)
            ->post(route('posts.upload-image'), [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertOk()
            ->assertJson(['url' => 'posts/uploaded.webp']);
    }

    public function test_admin_can_delete_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->for(User::factory()->create())->create();

        $this->actingAs($admin)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create(['title' => 'Published']);
        Post::factory()->for($user)->draft()->create(['title' => 'Draft']);

        $this->actingAs($user)
            ->get(route('posts.index', ['status' => PostStatus::Published->value]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Published')
            );
    }
}
