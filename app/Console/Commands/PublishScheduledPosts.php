<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publish all draft posts whose scheduled_at time has passed';

    public function handle(): int
    {
        $count = Post::readyToPublish()->update([
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        if ($count === 0) {
            $this->info('No scheduled posts are ready to publish.');

            return self::SUCCESS;
        }

        Cache::increment('blog:index:version');

        $this->info("Published {$count} post(s).");

        return self::SUCCESS;
    }
}
