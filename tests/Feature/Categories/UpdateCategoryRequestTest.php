<?php

namespace Tests\Feature\Categories;

use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCategoryRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data, ?Category $category = null): \Illuminate\Validation\Validator
    {
        $request = new UpdateCategoryRequest;

        if ($category) {
            $request->setRouteResolver(function () use ($category) {
                return new class($category)
                {
                    public function __construct(private Category $category) {}

                    public function parameter(string $key, mixed $default = null): mixed
                    {
                        return $key === 'category' ? $this->category : $default;
                    }
                };
            });
        }

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate(['name' => 'Updated Name'], $category);

        $this->assertFalse($validator->fails());
    }

    public function test_name_is_required(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([], $category);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_must_be_unique_across_other_categories(): void
    {
        Category::factory()->create(['name' => 'Technology']);
        $category = Category::factory()->create(['name' => 'Science']);

        $validator = $this->validate(['name' => 'Technology'], $category);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_uniqueness_ignores_own_category(): void
    {
        $category = Category::factory()->create(['name' => 'Technology']);

        $validator = $this->validate(['name' => 'Technology'], $category);

        $this->assertFalse($validator->fails());
    }

    public function test_name_max_255_characters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate(['name' => str_repeat('a', 256)], $category);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_description_is_nullable(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([
            'name' => 'Technology',
            'description' => null,
        ], $category);

        $this->assertFalse($validator->fails());
    }

    public function test_description_accepts_string_value(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([
            'name' => 'Technology',
            'description' => 'Posts about tech.',
        ], $category);

        $this->assertFalse($validator->fails());
    }

    public function test_authorize_returns_true(): void
    {
        $request = new UpdateCategoryRequest;

        $this->assertTrue($request->authorize());
    }
}
