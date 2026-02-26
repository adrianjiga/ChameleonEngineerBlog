<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/PostController';
import CoverImageUpload from '@/components/CoverImageUpload.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { autosave, index as postsIndex } from '@/routes/posts';
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

function toggleCategory(id: number) {
    const index = form.category_ids.indexOf(id);
    if (index === -1) {
        form.category_ids.push(id);
    } else {
        form.category_ids.splice(index, 1);
    }
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
                        setTimeout(() => { autosaveStatus.value = 'idle'; }, 2000);
                    },
                    onError: () => { autosaveStatus.value = 'idle'; },
                },
            );
        }, 2000);
    },
);
</script>

<template>
    <Head :title="`Edit: ${post.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Edit Post</h1>
                <span
                    v-if="autosaveStatus !== 'idle'"
                    class="text-muted-foreground flex items-center gap-1.5 text-sm"
                >
                    <CheckCircle v-if="autosaveStatus === 'saved'" class="size-4 text-green-500" />
                    {{ autosaveStatus === 'saving' ? 'Saving...' : 'Saved' }}
                </span>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Title -->
                <div class="space-y-1.5">
                    <Label for="title">Title <span class="text-destructive">*</span></Label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="border-input bg-background focus:ring-ring w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />
                    <p v-if="form.errors.title" class="text-destructive text-sm">{{ form.errors.title }}</p>
                </div>

                <!-- Content -->
                <div class="space-y-1.5">
                    <Label>Content <span class="text-destructive">*</span></Label>
                    <RichTextEditor v-model="form.content" />
                    <p v-if="form.errors.content" class="text-destructive text-sm">{{ form.errors.content }}</p>
                </div>

                <!-- Excerpt -->
                <div class="space-y-1.5">
                    <Label for="excerpt">Excerpt</Label>
                    <textarea
                        id="excerpt"
                        v-model="form.excerpt"
                        rows="2"
                        class="border-input bg-background focus:ring-ring w-full resize-none rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />
                </div>

                <!-- Cover image -->
                <div class="space-y-1.5">
                    <Label>Cover Image</Label>
                    <CoverImageUpload
                        v-model="form.featured_image"
                        :existing-image-url="post.featured_image ?? undefined"
                    />
                </div>

                <!-- Status + Scheduled at -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="published">Published</SelectItem>
                                <SelectItem value="unpublished">Unpublished</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="scheduled_at">Schedule At</Label>
                        <input
                            id="scheduled_at"
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            class="border-input bg-background focus:ring-ring w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                        />
                    </div>
                </div>

                <!-- Categories -->
                <div v-if="categories.length > 0" class="space-y-2">
                    <Label>Categories</Label>
                    <div class="flex flex-wrap gap-3">
                        <div
                            v-for="cat in categories"
                            :key="cat.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`cat-${cat.id}`"
                                :checked="form.category_ids.includes(cat.id)"
                                @update:checked="toggleCategory(cat.id)"
                            />
                            <Label :for="`cat-${cat.id}`" class="cursor-pointer font-normal">{{ cat.name }}</Label>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <details class="border-input rounded-md border p-4">
                    <summary class="cursor-pointer text-sm font-medium">SEO Settings</summary>
                    <div class="mt-4 space-y-4">
                        <div class="space-y-1.5">
                            <Label for="meta_title">Meta Title</Label>
                            <input
                                id="meta_title"
                                v-model="form.meta_title"
                                type="text"
                                class="border-input bg-background focus:ring-ring w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="meta_description">Meta Description</Label>
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="2"
                                class="border-input bg-background focus:ring-ring w-full resize-none rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                            />
                        </div>
                    </div>
                </details>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" as-child>
                        <a :href="postsIndex().url">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Post' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
