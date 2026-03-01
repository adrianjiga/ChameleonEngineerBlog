<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySlugCollisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_collision_is_resolved(): void
    {
        Category::factory()->create(['name' => 'Technology']);
        Category::factory()->create(['name' => 'Technology']);

        $this->assertSame(1, Category::where('slug', 'technology')->count());
        $this->assertSame(1, Category::where('slug', 'technology-2')->count());
    }

    public function test_category_route_key_is_slug(): void
    {
        $this->assertSame('slug', (new Category)->getRouteKeyName());
    }
}
