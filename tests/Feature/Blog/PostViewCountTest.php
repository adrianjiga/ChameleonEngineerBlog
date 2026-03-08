<?php

namespace Tests\Feature\Blog;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PostViewCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_visiting_a_published_post_increments_views(): void
    {
        $post = Post::factory()->published()->create(['views' => 0]);

        $this->get(route('blog.show', $post))->assertOk();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'views' => 1]);
    }

    public function test_visiting_a_published_post_twice_increments_views_to_two(): void
    {
        $post = Post::factory()->published()->create(['views' => 0]);

        $this->get(route('blog.show', $post))->assertOk();
        $this->get(route('blog.show', $post))->assertOk();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'views' => 2]);
    }

    public function test_visiting_preview_route_does_not_increment_views(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create(['views' => 0, 'status' => PostStatus::Draft]);

        $this->actingAs($author)
            ->get(route('posts.preview', $post))
            ->assertOk();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'views' => 0]);
    }
}
