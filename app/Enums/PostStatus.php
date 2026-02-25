<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            PostStatus::Draft => 'Draft',
            PostStatus::Published => 'Published',
            PostStatus::Unpublished => 'Unpublished',
        };
    }

    public function color(): string
    {
        return match ($this) {
            PostStatus::Draft => 'yellow',
            PostStatus::Published => 'green',
            PostStatus::Unpublished => 'red',
        };
    }
}
