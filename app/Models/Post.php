<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['reading_time', 'featured_image_urls'];

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $baseSlug = Str::slug($post->title);
                $slug = $baseSlug;
                $counter = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }
                $post->slug = $slug;
            }
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('title') && ! $post->isDirty('slug')) {
                $baseSlug = Str::slug($post->title);
                $slug = $baseSlug;
                $counter = 2;
                while (static::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }
                $post->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));

        return (int) max(1, ceil($wordCount / 200));
    }

    /** @return array<string, string> */
    public function getFeaturedImageUrlsAttribute(): array
    {
        if ($this->featured_image === null) {
            return [];
        }

        $pathInfo = pathinfo($this->featured_image);
        $dir = $pathInfo['dirname'];
        $basename = $pathInfo['filename'];

        $urls = ['original' => $this->featured_image];

        foreach (array_keys(config('images.sizes', [])) as $sizeName) {
            $urls[$sizeName] = "{$dir}/{$basename}_{$sizeName}.webp";
        }

        return $urls;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft)
            ->whereNotNull('scheduled_at');
    }

    public function scopeReadyToPublish(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%");
        });
    }
}
