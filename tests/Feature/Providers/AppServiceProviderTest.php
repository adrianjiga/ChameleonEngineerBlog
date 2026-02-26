<?php

namespace Tests\Feature\Providers;

use App\Services\ImageOptimizer;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_image_optimizer_is_bound_in_the_container(): void
    {
        $optimizer = app(ImageOptimizer::class);

        $this->assertInstanceOf(ImageOptimizer::class, $optimizer);
    }

    public function test_image_optimizer_is_a_singleton(): void
    {
        $first = app(ImageOptimizer::class);
        $second = app(ImageOptimizer::class);

        $this->assertSame($first, $second);
    }
}
