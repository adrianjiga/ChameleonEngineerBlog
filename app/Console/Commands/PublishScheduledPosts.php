<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publish all draft posts whose scheduled_at time has passed';

    public function handle(): int
    {
        $posts = Post::readyToPublish()->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts are ready to publish.');

            return self::SUCCESS;
        }

        foreach ($posts as $post) {
            $post->update([
                'status' => PostStatus::Published,
                'published_at' => now(),
            ]);
        }

        $this->info("Published {$posts->count()} post(s).");

        return self::SUCCESS;
    }
}
