<?php

namespace Tests\Feature\Services;

use App\Services\ImageOptimizer;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    private ImageOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->optimizer = new ImageOptimizer(app(FilesystemManager::class));
    }

    public function test_optimize_stores_webp_file_and_returns_path(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $path = $this->optimizer->optimize($file, 'posts');

        $this->assertStringStartsWith('posts/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::assertExists($path);
    }

    public function test_optimize_stores_to_specified_directory(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $path = $this->optimizer->optimize($file, 'uploads/custom');

        $this->assertStringStartsWith('uploads/custom/', $path);
    }

    public function test_generate_variants_creates_all_configured_sizes(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 1500, 1000);
        $originalPath = $this->optimizer->optimize($file, 'posts');

        $variants = $this->optimizer->generateVariants($originalPath);

        $sizes = config('images.sizes');
        $this->assertCount(count($sizes), $variants);

        foreach (array_keys($sizes) as $sizeName) {
            $this->assertArrayHasKey($sizeName, $variants);
            Storage::assertExists($variants[$sizeName]);
        }
    }

    public function test_generate_variants_returns_empty_array_when_file_not_found(): void
    {
        $variants = $this->optimizer->generateVariants('posts/nonexistent.webp');

        $this->assertSame([], $variants);
    }

    public function test_delete_with_variants_removes_original_and_all_size_variants(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 1500, 1000);
        $originalPath = $this->optimizer->optimize($file, 'posts');
        $variants = $this->optimizer->generateVariants($originalPath);

        $this->optimizer->deleteWithVariants($originalPath);

        Storage::assertMissing($originalPath);
        foreach ($variants as $variantPath) {
            Storage::assertMissing($variantPath);
        }
    }

    public function test_delete_with_variants_does_not_throw_when_files_missing(): void
    {
        $this->optimizer->deleteWithVariants('posts/nonexistent.webp');

        $this->assertTrue(true);
    }

    public function test_get_variant_urls_returns_original_and_all_size_keys(): void
    {
        $urls = $this->optimizer->getVariantUrls('posts/abc123.webp');

        $this->assertArrayHasKey('original', $urls);
        $this->assertSame('posts/abc123.webp', $urls['original']);

        foreach (array_keys(config('images.sizes')) as $sizeName) {
            $this->assertArrayHasKey($sizeName, $urls);
            $this->assertStringContainsString($sizeName, $urls[$sizeName]);
        }
    }

    public function test_get_variant_urls_uses_correct_path_structure(): void
    {
        $urls = $this->optimizer->getVariantUrls('posts/my-image.webp');

        $this->assertSame('posts/my-image_large.webp', $urls['large']);
        $this->assertSame('posts/my-image_medium.webp', $urls['medium']);
        $this->assertSame('posts/my-image_thumb.webp', $urls['thumb']);
    }
}
