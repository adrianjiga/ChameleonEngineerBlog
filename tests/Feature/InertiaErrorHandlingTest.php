<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InertiaErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $inertiaHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $version = file_exists(public_path('build/manifest.json'))
            ? hash_file('xxh128', public_path('build/manifest.json'))
            : null;

        $this->inertiaHeaders = array_filter([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ]);

        Route::middleware(['web'])->group(function (): void {
            Route::get('/_test/404', fn () => abort(404));
            Route::get('/_test/403', fn () => abort(403));
            Route::get('/_test/500', fn () => abort(500));
            Route::get('/_test/503', fn () => abort(503));
        });
    }

    public function test_inertia_404_renders_error_page_json(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders)
            ->get('/_test/404');

        $response->assertStatus(404);
        $response->assertJsonPath('component', 'ErrorPage');
    }

    public function test_inertia_403_renders_error_page_json(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders)
            ->get('/_test/403');

        $response->assertStatus(403);
        $response->assertJsonPath('component', 'ErrorPage');
    }

    public function test_inertia_500_renders_error_page_json(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders)
            ->get('/_test/500');

        $response->assertStatus(500);
        $response->assertJsonPath('component', 'ErrorPage');
    }

    public function test_inertia_503_renders_error_page_json(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders)
            ->get('/_test/503');

        $response->assertStatus(503);
        $response->assertJsonPath('component', 'ErrorPage');
    }

    public function test_non_inertia_requests_are_not_intercepted(): void
    {
        $response = $this->get('/_test/404');

        $response->assertStatus(404);
        $response->assertDontSee('"component":"ErrorPage"');
    }

    public function test_error_page_receives_status_prop(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders)
            ->get('/_test/404');

        $response->assertJsonPath('props.status', 404);
    }

    public function test_419_redirects_back_with_flash_error(): void
    {
        Route::middleware(['web'])->post('/_test/419', fn () => abort(419));

        $response = $this->post('/_test/419');

        $response->assertRedirect();
        $this->assertEquals(
            'Your session has expired. Please refresh and try again.',
            session('flash.error')
        );
    }

    public function test_404_for_nonexistent_blog_post_slug(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders)
            ->get('/blog/this-post-does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonPath('component', 'ErrorPage');
        $response->assertJsonPath('props.status', 404);
    }

    public function test_403_message_is_passed_as_prop_to_error_page(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $post = Post::factory()->for($userA)->create();

        $response = $this->actingAs($userB)
            ->withHeaders($this->inertiaHeaders)
            ->get(route('posts.edit', $post));

        $response->assertStatus(403);
        $response->assertJsonPath('component', 'ErrorPage');
        $response->assertJsonPath('props.status', 403);
    }
}
