<?php

namespace Tests\Feature\Commands;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupOrphanedImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
    }

    public function test_outputs_message_when_no_files_exist(): void
    {
        $this->artisan('posts:cleanup-images')
            ->expectsOutput('No files found in posts storage.')
            ->assertSuccessful();
    }

    public function test_outputs_message_when_no_orphans_found(): void
    {
        $post = Post::factory()->withFeaturedImage()->create();
        Storage::put($post->featured_image, 'image-data');

        $this->artisan('posts:cleanup-images')
            ->expectsOutput('No orphaned images found.')
            ->assertSuccessful();
    }

    public function test_deletes_files_not_referenced_by_any_post(): void
    {
        Storage::put('posts/orphan.webp', 'image-data');

        $this->artisan('posts:cleanup-images')->assertSuccessful();

        Storage::assertMissing('posts/orphan.webp');
    }

    public function test_outputs_count_of_deleted_orphans(): void
    {
        Storage::put('posts/orphan-a.webp', 'image-data');
        Storage::put('posts/orphan-b.webp', 'image-data');

        $this->artisan('posts:cleanup-images')
            ->expectsOutput('Deleted 2 orphaned file(s).')
            ->assertSuccessful();
    }

    public function test_does_not_delete_referenced_original_image(): void
    {
        $post = Post::factory()->withFeaturedImage()->create();
        Storage::put($post->featured_image, 'image-data');

        $this->artisan('posts:cleanup-images')->assertSuccessful();

        Storage::assertExists($post->featured_image);
    }

    public function test_does_not_delete_variant_files_of_referenced_image(): void
    {
        $post = Post::factory()->withFeaturedImage()->create();
        $info = pathinfo($post->featured_image);
        $sizes = array_keys(config('images.sizes'));

        Storage::put($post->featured_image, 'image-data');
        foreach ($sizes as $sizeName) {
            Storage::put("{$info['dirname']}/{$info['filename']}_{$sizeName}.webp", 'variant-data');
        }

        $this->artisan('posts:cleanup-images')
            ->expectsOutput('No orphaned images found.')
            ->assertSuccessful();
    }

    public function test_dry_run_lists_orphans_without_deleting(): void
    {
        Storage::put('posts/orphan.webp', 'image-data');

        $this->artisan('posts:cleanup-images --dry-run')
            ->expectsOutputToContain('dry run — nothing deleted')
            ->assertSuccessful();

        Storage::assertExists('posts/orphan.webp');
    }

    public function test_dry_run_lists_each_orphaned_file(): void
    {
        Storage::put('posts/orphan.webp', 'image-data');

        $this->artisan('posts:cleanup-images --dry-run')
            ->expectsOutputToContain('posts/orphan.webp')
            ->assertSuccessful();
    }

    public function test_deletes_orphaned_variants_of_deleted_post(): void
    {
        // Simulate a post that was deleted but left variant files behind
        Storage::put('posts/stale_large.webp', 'stale-data');
        Storage::put('posts/stale_medium.webp', 'stale-data');
        Storage::put('posts/stale_thumb.webp', 'stale-data');

        $this->artisan('posts:cleanup-images')->assertSuccessful();

        Storage::assertMissing('posts/stale_large.webp');
        Storage::assertMissing('posts/stale_medium.webp');
        Storage::assertMissing('posts/stale_thumb.webp');
    }
}
