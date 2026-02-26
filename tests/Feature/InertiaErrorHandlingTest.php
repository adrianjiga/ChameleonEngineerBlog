<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Route::middleware(['web'])->group(function (): void {
            Route::get('/_test/404', fn () => abort(404));
            Route::get('/_test/403', fn () => abort(403));
            Route::get('/_test/500', fn () => abort(500));
            Route::get('/_test/503', fn () => abort(503));
        });
    }

    public function test_404_renders_inertia_error_page(): void
    {
        $response = $this->get('/_test/404');

        $response->assertStatus(404);
        $response->assertInertia(fn (Assert $page) => $page->component('ErrorPage', false));
    }

    public function test_403_renders_inertia_error_page(): void
    {
        $response = $this->get('/_test/403');

        $response->assertStatus(403);
        $response->assertInertia(fn (Assert $page) => $page->component('ErrorPage', false));
    }

    public function test_500_renders_inertia_error_page(): void
    {
        $response = $this->get('/_test/500');

        $response->assertStatus(500);
        $response->assertInertia(fn (Assert $page) => $page->component('ErrorPage', false));
    }

    public function test_503_renders_inertia_error_page(): void
    {
        $response = $this->get('/_test/503');

        $response->assertStatus(503);
        $response->assertInertia(fn (Assert $page) => $page->component('ErrorPage', false));
    }

    public function test_error_page_receives_status_prop(): void
    {
        $response = $this->get('/_test/404');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage', false)
            ->where('status', 404)
        );
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
}
