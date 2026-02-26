<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    public function saved(Post $post): void
    {
        Cache::forget("post:{$post->id}");
        Cache::increment('blog:index:version');
    }

    public function deleted(Post $post): void
    {
        Cache::forget("post:{$post->id}");
        Cache::increment('blog:index:version');

        if ($post->featured_image) {
            try {
                app(ImageOptimizer::class)->deleteWithVariants($post->featured_image);
            } catch (\Throwable) {
                // Silently ignore — orphaned files are cleaned up by posts:cleanup-images
            }
        }
    }
}
