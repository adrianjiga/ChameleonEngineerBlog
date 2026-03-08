<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, ExternalLink } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/PostController';
import PostForm from '@/components/PostForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { autosave, index as postsIndex, preview } from '@/routes/posts';
import type { BreadcrumbItem, Category, Post } from '@/types';

const props = defineProps<{ post: Post; categories: Category[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Posts', href: postsIndex().url },
    { title: 'Edit Post' },
];

const form = useForm({
    title: props.post.title,
    content: props.post.content,
    excerpt: props.post.excerpt ?? '',
    status: props.post.status,
    featured_image: null as File | null,
    category_ids: props.post.categories?.map((c) => c.id) ?? [],
    meta_title: props.post.meta_title ?? '',
    meta_description: props.post.meta_description ?? '',
    scheduled_at: props.post.scheduled_at ?? '',
});

function submit() {
    form.submit(update(props.post), { forceFormData: true });
}

// Autosave
const autosaveStatus = ref<'idle' | 'saving' | 'saved'>('idle');
let autosaveTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => [form.title, form.content],
    () => {
        if (autosaveTimer) clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(() => {
            autosaveStatus.value = 'saving';
            router.patch(
                autosave(props.post).url,
                { title: form.title, content: form.content },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        autosaveStatus.value = 'saved';
                        setTimeout(() => {
                            autosaveStatus.value = 'idle';
                        }, 2000);
                    },
                    onError: () => {
                        autosaveStatus.value = 'idle';
                    },
                },
            );
        }, 2000);
    },
);

onBeforeUnmount(() => {
    if (autosaveTimer) {
        clearTimeout(autosaveTimer);
    }
});
</script>

<template>
    <Head :title="`Edit: ${post.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Edit Post</h1>
                <div class="flex items-center gap-3">
                    <span
                        v-if="autosaveStatus !== 'idle'"
                        class="flex items-center gap-1.5 text-sm text-muted-foreground"
                    >
                        <CheckCircle
                            v-if="autosaveStatus === 'saved'"
                            class="size-4 text-green-500"
                        />
                        {{
                            autosaveStatus === 'saving' ? 'Saving...' : 'Saved'
                        }}
                    </span>
                    <a
                        :href="preview(post).url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-muted"
                    >
                        <ExternalLink class="size-4" />
                        Preview
                    </a>
                </div>
            </div>

            <PostForm
                :form="form"
                :categories="categories"
                :existing-image-url="post.featured_image ?? undefined"
                submit-label="Update Post"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
