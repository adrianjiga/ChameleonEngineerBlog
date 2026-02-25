<?php

namespace Tests\Feature\Models;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_generates_slug_from_name(): void
    {
        $category = Category::create(['name' => 'Web Development']);

        $this->assertSame('web-development', $category->slug);
    }

    public function test_does_not_overwrite_explicit_slug(): void
    {
        $category = Category::create(['name' => 'Web Development', 'slug' => 'custom-slug']);

        $this->assertSame('custom-slug', $category->slug);
    }

    public function test_updates_slug_when_name_changes(): void
    {
        $category = Category::create(['name' => 'Original']);

        $category->update(['name' => 'Updated Name']);

        $this->assertSame('updated-name', $category->fresh()->slug);
    }

    public function test_belongs_to_many_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Tech']);
        $post = Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'Content',
            'status' => PostStatus::Draft,
        ]);
        $category->posts()->attach($post);

        $this->assertTrue($category->posts->contains($post));
    }

    public function test_description_is_nullable(): void
    {
        $category = Category::create(['name' => 'No Description']);

        $this->assertNull($category->description);
    }
}
