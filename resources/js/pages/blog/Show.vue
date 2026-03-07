<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Clock, Copy, Link as LinkIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BlogHeader from '@/components/BlogHeader.vue';
import { Badge } from '@/components/ui/badge';
import { formatDateLong } from '@/lib/date';
import { sanitizeHtml } from '@/lib/sanitize';
import { index as blogIndex, show as blogShow } from '@/routes/blog';
import type { Post } from '@/types';

const props = defineProps<{
    post: Post;
    relatedPosts: Post[];
    isPreview?: boolean;
}>();

const shareUrl = computed(() => encodeURIComponent(window.location.href));
const shareTitle = computed(() => encodeURIComponent(props.post.meta_title ?? props.post.title));

const copied = ref(false);
function copyLink() {
    navigator.clipboard.writeText(window.location.href);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}
</script>

<template>
    <Head :title="post.meta_title ?? post.title">
        <meta
            v-if="post.meta_description ?? post.excerpt"
            name="description"
            :content="post.meta_description ?? post.excerpt"
            head-key="description"
        />
        <meta property="og:type" content="article" head-key="og:type" />
        <meta
            property="og:title"
            :content="post.meta_title ?? post.title"
            head-key="og:title"
        />
        <meta
            v-if="post.meta_description ?? post.excerpt"
            property="og:description"
            :content="post.meta_description ?? post.excerpt"
            head-key="og:description"
        />
        <meta
            v-if="post.featured_image_urls?.large ?? post.featured_image"
            property="og:image"
            :content="post.featured_image_urls?.large ?? post.featured_image"
            head-key="og:image"
        />
    </Head>

    <!-- Preview banner -->
    <div
        v-if="isPreview"
        class="fixed inset-x-0 top-0 z-50 bg-yellow-400 px-4 py-2 text-center text-sm font-medium text-yellow-900"
    >
        Preview — this post is not yet published
    </div>

    <div :class="['min-h-screen bg-background', isPreview && 'pt-10']">
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

            <!-- Share buttons -->
            <section class="mt-12 border-t pt-8">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                    Share this post
                </h2>
                <div class="flex flex-wrap gap-2">
                    <!-- X / Twitter -->
                    <a
                        :href="`https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareTitle}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Share on X / Twitter"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.733-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                        X / Twitter
                    </a>

                    <!-- LinkedIn -->
                    <a
                        :href="`https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Share on LinkedIn"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                        </svg>
                        LinkedIn
                    </a>

                    <!-- Facebook -->
                    <a
                        :href="`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Share on Facebook"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Facebook
                    </a>

                    <!-- Reddit -->
                    <a
                        :href="`https://www.reddit.com/submit?url=${shareUrl}&title=${shareTitle}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Share on Reddit"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z" />
                        </svg>
                        Reddit
                    </a>

                    <!-- WhatsApp -->
                    <a
                        :href="`https://wa.me/?text=${shareTitle}%20${shareUrl}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Share on WhatsApp"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z" />
                        </svg>
                        WhatsApp
                    </a>

                    <!-- BlueSky -->
                    <a
                        :href="`https://bsky.app/intent/compose?text=${shareTitle}%20${shareUrl}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Share on BlueSky"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 0 1-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.204-.659-.298-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8z" />
                        </svg>
                        BlueSky
                    </a>

                    <!-- Copy link -->
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        aria-label="Copy link"
                        @click="copyLink"
                    >
                        <Check v-if="copied" class="size-4 text-green-500" />
                        <LinkIcon v-else class="size-4" />
                        {{ copied ? 'Copied!' : 'Copy link' }}
                    </button>
                </div>
            </section>

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
