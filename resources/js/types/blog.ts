import type { User } from './auth';

export type PostStatus = 'draft' | 'published' | 'unpublished';

export type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    posts_count?: number;
    created_at: string;
    updated_at: string;
};

export type Post = {
    id: number;
    user_id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    featured_image: string | null;
    featured_image_urls: Record<string, string>;
    status: PostStatus;
    published_at: string | null;
    scheduled_at: string | null;
    meta_title: string | null;
    meta_description: string | null;
    reading_time: number;
    created_at: string;
    updated_at: string;
    user?: User;
    categories?: Category[];
};

export type PaginatedPosts = {
    data: Post[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};
