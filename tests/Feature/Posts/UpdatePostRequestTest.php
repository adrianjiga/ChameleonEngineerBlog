<?php

namespace Tests\Feature\Posts;

use App\Enums\PostStatus;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdatePostRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdatePostRequest;

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Updated content.',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_title_is_required(): void
    {
        $validator = $this->validate(['content' => 'Some content.']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_content_is_required(): void
    {
        $validator = $this->validate(['title' => 'Updated Post']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }

    public function test_excerpt_max_500_characters(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'excerpt' => str_repeat('a', 501),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('excerpt', $validator->errors()->toArray());
    }

    public function test_status_must_be_valid_enum_value(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'status' => 'invalid-status',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_status_accepts_valid_enum_values(): void
    {
        foreach (PostStatus::cases() as $status) {
            $validator = $this->validate([
                'title' => 'Updated Post',
                'content' => 'Content.',
                'status' => $status->value,
            ]);

            $this->assertFalse($validator->fails(), "Failed for status: {$status->value}");
        }
    }

    public function test_category_ids_must_exist_in_database(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'category_ids' => [9999],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_ids.0', $validator->errors()->toArray());
    }

    public function test_category_ids_accepts_valid_ids(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'category_ids' => [$category->id],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_scheduled_at_accepts_now_or_future(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'scheduled_at' => now()->addMinute()->toDateTimeString(),
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_scheduled_at_rejects_past_dates(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'scheduled_at' => now()->subHour()->toDateTimeString(),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('scheduled_at', $validator->errors()->toArray());
    }

    public function test_nullable_fields_all_pass_when_null(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'excerpt' => null,
            'status' => null,
            'featured_image' => null,
            'category_ids' => null,
            'meta_title' => null,
            'meta_description' => null,
            'scheduled_at' => null,
            'published_at' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_authorize_returns_true(): void
    {
        $request = new UpdatePostRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_featured_image_must_be_an_image_file(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'featured_image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('featured_image', $validator->errors()->toArray());
    }

    public function test_featured_image_max_size_is_5mb(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Post',
            'content' => 'Content.',
            'featured_image' => UploadedFile::fake()->image('photo.jpg')->size(5121),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('featured_image', $validator->errors()->toArray());
    }
}
