<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/PostController';
import PostForm from '@/components/PostForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as postsIndex } from '@/routes/posts';
import type { BreadcrumbItem, Category } from '@/types';

defineProps<{ categories: Category[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Posts', href: postsIndex().url },
    { title: 'New Post' },
];

const form = useForm({
    title: '',
    content: '',
    excerpt: '',
    status: 'draft' as string,
    featured_image: null as File | null,
    category_ids: [] as number[],
    meta_title: '',
    meta_description: '',
    scheduled_at: '',
});

function submit() {
    form.submit(store(), { forceFormData: true });
}
</script>

<template>
    <Head title="New Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <h1 class="mb-6 text-2xl font-semibold">New Post</h1>

            <PostForm
                :form="form"
                :categories="categories"
                submit-label="Create Post"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
