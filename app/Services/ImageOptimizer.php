<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ImageOptimizer
{
    public function __construct(
        private FilesystemManager $filesystem
    ) {}

    public function optimize(UploadedFile $file, string $directory = 'posts'): string
    {
        $image = ImageManager::gd()->read($file->getRealPath());
        $path = "{$directory}/".Str::uuid().'.webp';

        $this->filesystem->disk()->put($path, $image->toWebp(config('images.quality', 80))->toString());

        return $path;
    }

    /** @return array<string, string> */
    public function generateVariants(string $path, ?string $directory = null): array
    {
        $contents = $this->filesystem->disk()->get($path);

        if ($contents === null) {
            return [];
        }

        $pathInfo = pathinfo($path);
        $dir = $directory ?? $pathInfo['dirname'];
        $basename = $pathInfo['filename'];
        $quality = config('images.quality', 80);
        $variants = [];

        foreach (config('images.sizes', []) as $sizeName => $width) {
            $image = ImageManager::gd()->read($contents);
            $image->scaleDown(width: $width);

            $variantPath = "{$dir}/{$basename}_{$sizeName}.webp";
            $this->filesystem->disk()->put($variantPath, $image->toWebp($quality)->toString());

            $variants[$sizeName] = $variantPath;
        }

        return $variants;
    }

    public function deleteWithVariants(string $path): void
    {
        $this->filesystem->disk()->delete($path);

        $pathInfo = pathinfo($path);
        $dir = $pathInfo['dirname'];
        $basename = $pathInfo['filename'];

        foreach (array_keys(config('images.sizes', [])) as $sizeName) {
            $this->filesystem->disk()->delete("{$dir}/{$basename}_{$sizeName}.webp");
        }
    }

    /** @return array<string, string> */
    public function getVariantUrls(string $path): array
    {
        $pathInfo = pathinfo($path);
        $dir = $pathInfo['dirname'];
        $basename = $pathInfo['filename'];

        $urls = ['original' => $path];

        foreach (array_keys(config('images.sizes', [])) as $sizeName) {
            $urls[$sizeName] = "{$dir}/{$basename}_{$sizeName}.webp";
        }

        return $urls;
    }
}
