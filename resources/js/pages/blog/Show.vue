<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { useSanitizedHtml } from '@/composables/useSanitizedHtml';
import { home } from '@/routes';
import { index as blogIndex, show as blogShow } from '@/routes/blog';
import type { Post } from '@/types';

defineProps<{
    post: Post;
    relatedPosts: Post[];
}>();

const { sanitize } = useSanitizedHtml();
</script>

<template>
    <Head :title="post.title" />

    <div class="bg-background min-h-screen">
        <!-- Header -->
        <header class="border-b">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                <Link :href="home()" class="text-xl font-bold">Chameleon Engineer</Link>
                <nav class="flex gap-4 text-sm">
                    <Link :href="home()" class="text-muted-foreground hover:text-foreground transition-colors">Home</Link>
                    <Link :href="blogIndex()" class="text-muted-foreground hover:text-foreground transition-colors">Blog</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-10">
            <!-- Back link -->
            <Link
                :href="blogIndex()"
                class="text-muted-foreground hover:text-foreground mb-8 inline-flex items-center gap-1 text-sm transition-colors"
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
                <h1 class="mb-4 text-4xl font-bold leading-tight">{{ post.title }}</h1>
                <div class="text-muted-foreground flex items-center gap-3 text-sm">
                    <span class="flex items-center gap-1">
                        <Clock class="size-4" />
                        {{ post.reading_time }} min read
                    </span>
                    <span v-if="post.published_at">
                        {{ new Date(post.published_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </span>
                </div>
            </header>

            <!-- Featured image -->
            <div v-if="post.featured_image" class="mb-8 overflow-hidden rounded-xl">
                <img
                    :src="post.featured_image_urls.large ?? post.featured_image"
                    :alt="post.title"
                    class="w-full object-cover"
                />
            </div>

            <!-- Post content -->
            <article
                class="prose dark:prose-invert prose-lg max-w-none"
                v-html="sanitize(post.content)"
            />

            <!-- Related posts -->
            <section v-if="relatedPosts.length > 0" class="mt-16">
                <h2 class="mb-6 text-2xl font-semibold">Related Posts</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <article
                        v-for="related in relatedPosts"
                        :key="related.id"
                        class="border-border rounded-xl border p-4"
                    >
                        <h3 class="line-clamp-2 font-medium leading-tight">
                            <Link :href="blogShow(related)" class="hover:underline">
                                {{ related.title }}
                            </Link>
                        </h3>
                        <p class="text-muted-foreground mt-1 text-xs">
                            {{ related.reading_time }} min read
                        </p>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
