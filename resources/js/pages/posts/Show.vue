<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Pencil } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useSanitizedHtml } from '@/composables/useSanitizedHtml';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { edit, index as postsIndex } from '@/routes/posts';
import type { BreadcrumbItem, Post } from '@/types';

const props = defineProps<{ post: Post }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Posts', href: postsIndex().url },
    { title: props.post.title },
];

const statusColor: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    unpublished: 'outline',
};

const { sanitize } = useSanitizedHtml();
</script>

<template>
    <Head :title="post.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <!-- Header -->
            <div class="mb-6 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link :href="postsIndex()">
                            <ArrowLeft class="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-xl font-semibold">{{ post.title }}</h1>
                        <div class="text-muted-foreground mt-1 flex items-center gap-2 text-sm">
                            <Badge :variant="statusColor[post.status] ?? 'outline'" class="text-xs">
                                {{ post.status }}
                            </Badge>
                            <span class="flex items-center gap-1">
                                <Clock class="size-3" />
                                {{ post.reading_time }} min read
                            </span>
                        </div>
                    </div>
                </div>
                <Button as-child>
                    <Link :href="edit(post)">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>
            </div>

            <!-- Categories -->
            <div v-if="post.categories?.length" class="mb-4 flex flex-wrap gap-1">
                <Badge
                    v-for="cat in post.categories"
                    :key="cat.id"
                    variant="secondary"
                >
                    {{ cat.name }}
                </Badge>
            </div>

            <!-- Featured image -->
            <div v-if="post.featured_image" class="mb-6 overflow-hidden rounded-xl">
                <img
                    :src="post.featured_image_urls.large ?? post.featured_image"
                    :alt="post.title"
                    class="w-full object-cover"
                />
            </div>

            <!-- Content -->
            <article
                class="prose dark:prose-invert prose-sm max-w-none"
                v-html="sanitize(post.content)"
            />
        </div>
    </AppLayout>
</template>
