<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6, true),
            'excerpt' => fake()->optional()->sentence(20),
            'content' => implode("\n\n", fake()->paragraphs(4)),
            'featured_image' => null,
            'status' => PostStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
            'scheduled_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'scheduled_at' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Draft,
            'published_at' => null,
            'scheduled_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Draft,
            'published_at' => null,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function withFeaturedImage(): static
    {
        return $this->state(fn () => [
            'featured_image' => 'posts/'.fake()->uuid().'.webp',
        ]);
    }
}
