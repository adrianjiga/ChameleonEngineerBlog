<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    public function __construct(private ImageOptimizer $imageOptimizer) {}

    public function saved(Post $post): void
    {
        $this->bustCache($post);
    }

    public function deleted(Post $post): void
    {
        $this->bustCache($post);

        if ($post->featured_image) {
            try {
                $this->imageOptimizer->deleteWithVariants($post->featured_image);
            } catch (\Throwable) {
                // Silently ignore — orphaned files are cleaned up by posts:cleanup-images
            }
        }
    }

    private function bustCache(Post $post): void
    {
        Cache::forget("post:{$post->id}");
        Cache::increment('blog:index:version');
    }
}
