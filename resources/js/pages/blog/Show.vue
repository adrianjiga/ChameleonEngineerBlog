<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock } from 'lucide-vue-next';
import BlogHeader from '@/components/BlogHeader.vue';
import { Badge } from '@/components/ui/badge';
import { formatDateLong } from '@/lib/date';
import { sanitizeHtml } from '@/lib/sanitize';
import { index as blogIndex, show as blogShow } from '@/routes/blog';
import type { Post } from '@/types';

defineProps<{
    post: Post;
    relatedPosts: Post[];
}>();

</script>

<template>
    <Head :title="post.title" />

    <div class="min-h-screen bg-background">
        <BlogHeader max-width="max-w-3xl" />

        <main class="mx-auto max-w-3xl px-4 py-10">
            <!-- Back link -->
            <Link
                :href="blogIndex()"
                class="mb-8 inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                Back to Blog
            </Link>

            <!-- Post header -->
            <header class="mb-8">
                <div class="mb-3 flex flex-wrap gap-1">
                    <Badge
                        v-for="category in post.categories"
                        :key="category.id"
                        variant="secondary"
                    >
                        {{ category.name }}
                    </Badge>
                </div>
                <h1 class="mb-4 text-4xl leading-tight font-bold">
                    {{ post.title }}
                </h1>
                <div
                    class="flex items-center gap-3 text-sm text-muted-foreground"
                >
                    <span class="flex items-center gap-1">
                        <Clock class="size-4" />
                        {{ post.reading_time }} min read
                    </span>
                    <span v-if="post.published_at">
                        {{ formatDateLong(post.published_at) }}
                    </span>
                </div>
            </header>

            <!-- Featured image -->
            <div
                v-if="post.featured_image"
                class="mb-8 overflow-hidden rounded-xl"
            >
                <img
                    :src="post.featured_image_urls.large ?? post.featured_image"
                    :alt="post.title"
                    class="w-full object-cover"
                />
            </div>

            <!-- Post content -->
            <article
                class="prose dark:prose-invert prose-lg max-w-none"
                v-html="sanitizeHtml(post.content)"
            />

            <!-- Related posts -->
            <section v-if="relatedPosts.length > 0" class="mt-16">
                <h2 class="mb-6 text-2xl font-semibold">Related Posts</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <article
                        v-for="related in relatedPosts"
                        :key="related.id"
                        class="rounded-xl border border-border p-4"
                    >
                        <h3 class="line-clamp-2 leading-tight font-medium">
                            <Link
                                :href="blogShow(related)"
                                class="hover:underline"
                            >
                                {{ related.title }}
                            </Link>
                        </h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ related.reading_time }} min read
                        </p>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
