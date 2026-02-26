<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $regularUsers = User::factory(3)->create();
        $allAuthors = $regularUsers->prepend($admin);

        $categories = Category::factory(7)->create();

        $publishedPosts = Post::factory(12)
            ->published()
            ->recycle($allAuthors)
            ->create();

        Post::factory(3)
            ->draft()
            ->recycle($allAuthors)
            ->create();

        Post::factory(2)
            ->scheduled()
            ->recycle($allAuthors)
            ->create();

        $publishedPosts->each(function (Post $post) use ($categories) {
            $post->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')
            );
        });

        $this->command?->info('✓ Seeding complete.');
        $this->command?->info('  Admin credentials:');
        $this->command?->info('  Email:    admin@example.com');
        $this->command?->info('  Password: password');
    }
}
