<?php

namespace Tests\Feature\Observers;

use App\Models\Post;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PostObserverTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $attributes = []): Post
    {
        return Post::factory()->for(User::factory()->create())->create($attributes);
    }

    public function test_saved_forgets_post_cache_key(): void
    {
        Cache::spy();

        $post = $this->makePost();

        Cache::shouldHaveReceived('forget')->with("post:{$post->id}")->once();
    }

    public function test_saved_increments_blog_index_version(): void
    {
        Cache::spy();

        $this->makePost();

        Cache::shouldHaveReceived('increment')->with('blog:index:version')->once();
    }

    public function test_updating_a_post_also_invalidates_cache(): void
    {
        $post = $this->makePost();

        Cache::spy();

        $post->update(['title' => 'Updated Title']);

        Cache::shouldHaveReceived('forget')->with("post:{$post->id}")->once();
        Cache::shouldHaveReceived('increment')->with('blog:index:version')->once();
    }

    public function test_deleted_forgets_post_cache_key(): void
    {
        $post = $this->makePost();

        Cache::spy();

        $post->delete();

        Cache::shouldHaveReceived('forget')->with("post:{$post->id}")->once();
    }

    public function test_deleted_increments_blog_index_version(): void
    {
        $post = $this->makePost();

        Cache::spy();

        $post->delete();

        Cache::shouldHaveReceived('increment')->with('blog:index:version')->once();
    }

    public function test_deleted_calls_delete_with_variants_when_featured_image_set(): void
    {
        $optimizer = $this->mock(ImageOptimizer::class);
        $optimizer->shouldReceive('deleteWithVariants')->with('posts/image.webp')->once();

        $post = $this->makePost(['featured_image' => 'posts/image.webp']);
        $post->delete();
    }

    public function test_deleted_does_not_call_delete_with_variants_when_no_image(): void
    {
        $optimizer = $this->mock(ImageOptimizer::class);
        $optimizer->shouldNotReceive('deleteWithVariants');

        $post = $this->makePost(['featured_image' => null]);
        $post->delete();
    }

    public function test_deleted_swallows_exception_from_image_optimizer(): void
    {
        $optimizer = $this->mock(ImageOptimizer::class);
        $optimizer->shouldReceive('deleteWithVariants')->andThrow(new \RuntimeException('Storage failure'));

        $post = $this->makePost(['featured_image' => 'posts/image.webp']);

        // Should not throw
        $post->delete();

        $this->assertNull(Post::find($post->id));
    }
}
