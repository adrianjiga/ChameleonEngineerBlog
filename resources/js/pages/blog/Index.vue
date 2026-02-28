<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';
import BlogHeader from '@/components/BlogHeader.vue';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/date';
import { index as blogIndex, show as blogShow } from '@/routes/blog';
import type { Category, PaginatedPosts } from '@/types';

const props = defineProps<{
    posts: PaginatedPosts;
    categories: Category[];
    filters: { search: string | null; category: string | null };
}>();

const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(
        blogIndex().url,
        {
            search: search.value || undefined,
            category: props.filters.category || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function filterByCategory(slug: string | null) {
    router.get(
        blogIndex().url,
        { category: slug || undefined, search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Blog" />

    <div class="min-h-screen bg-background">
        <BlogHeader />

        <main class="mx-auto max-w-5xl px-4 py-10">
            <!-- Hero -->
            <div class="mb-10 text-center">
                <h1 class="mb-2 text-4xl font-bold">The Blog</h1>
                <p class="text-muted-foreground">
                    Thoughts, tutorials, and insights on software engineering
                </p>
            </div>

            <!-- Search -->
            <div class="mb-6 flex gap-2">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search posts..."
                        class="w-full rounded-md border border-input bg-background py-2 pr-4 pl-9 text-sm outline-none focus:ring-2 focus:ring-ring"
                        @keydown.enter="applySearch"
                    />
                </div>
                <button
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    @click="applySearch"
                >
                    Search
                </button>
            </div>

            <!-- Category pills -->
            <div class="mb-8 flex flex-wrap gap-2">
                <button
                    :class="[
                        'rounded-full border px-3 py-1 text-sm transition-colors',
                        !filters.category
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input bg-background hover:bg-muted',
                    ]"
                    @click="filterByCategory(null)"
                >
                    All
                </button>
                <button
                    v-for="category in categories"
                    :key="category.id"
                    :class="[
                        'rounded-full border px-3 py-1 text-sm transition-colors',
                        filters.category === category.slug
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input bg-background hover:bg-muted',
                    ]"
                    @click="filterByCategory(category.slug)"
                >
                    {{ category.name }}
                </button>
            </div>

            <!-- Post grid -->
            <div
                v-if="posts.data.length > 0"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="post in posts.data"
                    :key="post.id"
                    class="flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        v-if="post.featured_image"
                        class="aspect-video overflow-hidden"
                    >
                        <img
                            :src="
                                post.featured_image_urls.medium ??
                                post.featured_image
                            "
                            :alt="post.title"
                            class="h-full w-full object-cover transition-transform hover:scale-105"
                        />
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-5">
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="cat in post.categories"
                                :key="cat.id"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ cat.name }}
                            </Badge>
                        </div>
                        <h2
                            class="line-clamp-2 text-lg leading-tight font-semibold"
                        >
                            <Link
                                :href="blogShow(post)"
                                class="hover:underline"
                                >{{ post.title }}</Link
                            >
                        </h2>
                        <p
                            v-if="post.excerpt"
                            class="line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ post.excerpt }}
                        </p>
                        <div
                            class="mt-auto flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span>{{ post.reading_time }} min read</span>
                            <span>·</span>
                            <span>{{ formatDate(post.published_at) }}</span>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-else class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No posts found.</p>
                <p class="text-sm">Try adjusting your search or filter.</p>
            </div>

            <!-- Pagination -->
            <nav
                v-if="posts.last_page > 1"
                class="mt-10 flex justify-center gap-1"
            >
                <template v-for="link in posts.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'border border-input bg-background hover:bg-muted',
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="rounded-md px-3 py-1.5 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </main>
    </div>
</template>
