<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/PostController';
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

function toggleCategory(id: number) {
    const index = form.category_ids.indexOf(id);
    if (index === -1) {
        form.category_ids.push(id);
    } else {
        form.category_ids.splice(index, 1);
    }
}
</script>

<template>
    <Head title="New Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <h1 class="mb-6 text-2xl font-semibold">New Post</h1>

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Title -->
                <div class="space-y-1.5">
                    <Label for="title"
                        >Title <span class="text-destructive">*</span></Label
                    >
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        placeholder="Post title"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                    />
                    <p
                        v-if="form.errors.title"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <!-- Content -->
                <div class="space-y-1.5">
                    <Label
                        >Content <span class="text-destructive">*</span></Label
                    >
                    <RichTextEditor
                        v-model="form.content"
                        placeholder="Start writing your post..."
                    />
                    <p
                        v-if="form.errors.content"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.content }}
                    </p>
                </div>

                <!-- Excerpt -->
                <div class="space-y-1.5">
                    <Label for="excerpt">Excerpt</Label>
                    <textarea
                        id="excerpt"
                        v-model="form.excerpt"
                        rows="2"
                        placeholder="Short description (optional)"
                        class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                    />
                    <p
                        v-if="form.errors.excerpt"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.excerpt }}
                    </p>
                </div>

                <!-- Cover image -->
                <div class="space-y-1.5">
                    <Label>Cover Image</Label>
                    <CoverImageUpload v-model="form.featured_image" />
                    <p
                        v-if="form.errors.featured_image"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.featured_image }}
                    </p>
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
                                <SelectItem value="published"
                                    >Published</SelectItem
                                >
                                <SelectItem value="unpublished"
                                    >Unpublished</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors.status"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="scheduled_at">Schedule At</Label>
                        <input
                            id="scheduled_at"
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p
                            v-if="form.errors.scheduled_at"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.scheduled_at }}
                        </p>
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
                            <Label
                                :for="`cat-${cat.id}`"
                                class="cursor-pointer font-normal"
                                >{{ cat.name }}</Label
                            >
                        </div>
                    </div>
                    <p
                        v-if="form.errors.category_ids"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.category_ids }}
                    </p>
                </div>

                <!-- SEO -->
                <details class="rounded-md border border-input p-4">
                    <summary class="cursor-pointer text-sm font-medium">
                        SEO Settings
                    </summary>
                    <div class="mt-4 space-y-4">
                        <div class="space-y-1.5">
                            <Label for="meta_title">Meta Title</Label>
                            <input
                                id="meta_title"
                                v-model="form.meta_title"
                                type="text"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="meta_description"
                                >Meta Description</Label
                            >
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="2"
                                class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
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
                        {{ form.processing ? 'Saving...' : 'Create Post' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
