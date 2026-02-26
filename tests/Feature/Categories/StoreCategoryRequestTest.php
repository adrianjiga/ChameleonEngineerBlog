<?php

namespace Tests\Feature\Categories;

use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCategoryRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreCategoryRequest;

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate(['name' => 'Technology']);

        $this->assertFalse($validator->fails());
    }

    public function test_name_is_required(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Technology']);

        $validator = $this->validate(['name' => 'Technology']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_max_255_characters(): void
    {
        $validator = $this->validate(['name' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_description_is_nullable(): void
    {
        $validator = $this->validate([
            'name' => 'Technology',
            'description' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_description_accepts_string_value(): void
    {
        $validator = $this->validate([
            'name' => 'Technology',
            'description' => 'Posts about tech.',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_authorize_returns_true(): void
    {
        $request = new StoreCategoryRequest;

        $this->assertTrue($request->authorize());
    }
}
