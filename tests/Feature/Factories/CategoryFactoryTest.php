<?php

namespace Tests\Feature\Factories;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_category_with_required_fields(): void
    {
        $category = Category::factory()->create();

        $this->assertNotEmpty($category->name);
        $this->assertNotEmpty($category->slug);
    }

    public function test_slug_is_derived_from_name(): void
    {
        $category = Category::factory()->create(['name' => 'Web Development']);

        $this->assertSame('web-development', $category->slug);
    }

    public function test_description_is_nullable(): void
    {
        $category = Category::factory()->create(['description' => null]);

        $this->assertNull($category->description);
    }

    public function test_can_create_multiple_categories(): void
    {
        Category::factory(5)->create();

        $this->assertDatabaseCount('categories', 5);
    }
}
