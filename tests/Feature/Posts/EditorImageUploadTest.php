<?php

namespace Tests\Feature\Posts;

use App\Models\Post;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
    }

    public function test_upload_image_stores_webp_file_on_disk(): void
    {
        $user = User::factory()->create();

        $this->mock(ImageOptimizer::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('optimize')->once()->andReturnUsing(function ($file) {
                $path = 'posts/test-image.webp';
                Storage::put($path, 'fake-webp-content');

                return $path;
            });
        });

        $response = $this->actingAs($user)
            ->post(route('posts.upload-image'), [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertOk();

        $url = $response->json('url');
        Storage::assertExists($url);
    }

    public function test_upload_image_returns_posts_prefixed_path(): void
    {
        $user = User::factory()->create();

        $this->mock(ImageOptimizer::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('optimize')->once()->andReturn('posts/abc123.webp');
        });

        $this->actingAs($user)
            ->post(route('posts.upload-image'), [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('url', fn ($url) => str_starts_with($url, 'posts/') && str_ends_with($url, '.webp'));
    }

    public function test_upload_image_rejects_gif_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('posts.upload-image'), [
                'image' => UploadedFile::fake()->create('animation.gif', 100, 'image/gif'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    public function test_content_with_uploaded_image_url_saves_correctly(): void
    {
        $user = User::factory()->create();

        $this->mock(ImageOptimizer::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('optimize')->andReturn('posts/abc123.webp');
            $mock->shouldReceive('generateVariants')->andReturn([]);
        });

        $uploadedUrl = 'posts/abc123.webp';
        $content = "<p>Hello</p><img src=\"{$uploadedUrl}\"><p>World</p>";

        $this->actingAs($user)
            ->post(route('posts.store'), [
                'title' => 'Post with uploaded image',
                'content' => $content,
            ]);

        $post = Post::where('title', 'Post with uploaded image')->first();
        $this->assertStringContainsString($uploadedUrl, $post->content);
    }

    public function test_editor_upload_does_not_generate_responsive_variants(): void
    {
        $user = User::factory()->create();

        $this->mock(ImageOptimizer::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('optimize')->once()->andReturnUsing(function ($file) {
                $path = 'posts/abc123.webp';
                Storage::put($path, 'fake-webp-content');

                return $path;
            });
            // generateVariants should NOT be called for editor uploads
            $mock->shouldNotReceive('generateVariants');
        });

        $this->actingAs($user)
            ->post(route('posts.upload-image'), [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertOk();
    }
}
