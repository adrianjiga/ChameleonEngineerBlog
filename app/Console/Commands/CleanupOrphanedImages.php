<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedImages extends Command
{
    protected $signature = 'posts:cleanup-images {--dry-run : List orphaned files without deleting them}';

    protected $description = 'Delete image files from storage that are no longer referenced by any post';

    public function handle(): int
    {
        $allFiles = collect(Storage::disk()->allFiles('posts'));

        if ($allFiles->isEmpty()) {
            $this->info('No files found in posts storage.');

            return self::SUCCESS;
        }

        $orphans = $allFiles->diff($this->knownPaths());

        if ($orphans->isEmpty()) {
            $this->info('No orphaned images found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Found {$orphans->count()} orphaned file(s) (dry run — nothing deleted):");
            $orphans->each(fn (string $file) => $this->line("  {$file}"));

            return self::SUCCESS;
        }

        $orphans->each(fn (string $file) => Storage::disk()->delete($file));

        $this->info("Deleted {$orphans->count()} orphaned file(s).");

        return self::SUCCESS;
    }

    private function knownPaths(): Collection
    {
        $known = collect();
        $sizes = array_keys(config('images.sizes', []));

        Post::whereNotNull('featured_image')->pluck('featured_image')
            ->each(function (string $imagePath) use ($known, $sizes) {
                $known->push($imagePath);

                $info = pathinfo($imagePath);
                foreach ($sizes as $sizeName) {
                    $known->push("{$info['dirname']}/{$info['filename']}_{$sizeName}.webp");
                }
            });

        return $known;
    }
}
