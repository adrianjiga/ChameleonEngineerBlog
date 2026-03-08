<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- InertiaForm is a reactive proxy designed to be mutated directly */
import { Link } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import CoverImageUpload from '@/components/CoverImageUpload.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index as postsIndex } from '@/routes/posts';
import type { Category } from '@/types';

type PostFormData = {
    title: string;
    content: string;
    excerpt: string;
    status: string;
    featured_image: File | null;
    category_ids: number[];
    meta_title: string;
    meta_description: string;
    scheduled_at: string;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<PostFormData>;
        categories: Category[];
        existingImageUrl?: string;
        submitLabel?: string;
    }>(),
    { existingImageUrl: undefined, submitLabel: 'Save' },
);

const emit = defineEmits<{ submit: [] }>();

function toggleCategory(id: number) {
    const index = props.form.category_ids.indexOf(id);
    if (index === -1) {
        props.form.category_ids.push(id);
    } else {
        props.form.category_ids.splice(index, 1);
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <!-- Title -->
        <div class="space-y-1.5">
            <Label for="title"
                >Title <span class="text-destructive">*</span></Label
            >
            <Input
                id="title"
                v-model="form.title"
                type="text"
                placeholder="Post title"
            />
            <p v-if="form.errors.title" class="text-sm text-destructive">
                {{ form.errors.title }}
            </p>
        </div>

        <!-- Content -->
        <div class="space-y-1.5">
            <Label>Content <span class="text-destructive">*</span></Label>
            <RichTextEditor v-model="form.content" />
            <p v-if="form.errors.content" class="text-sm text-destructive">
                {{ form.errors.content }}
            </p>
        </div>

        <!-- Excerpt -->
        <div class="space-y-1.5">
            <Label for="excerpt">Excerpt</Label>
            <Textarea
                id="excerpt"
                v-model="form.excerpt"
                rows="2"
                placeholder="Short description (optional)"
                class="resize-none"
            />
            <p v-if="form.errors.excerpt" class="text-sm text-destructive">
                {{ form.errors.excerpt }}
            </p>
        </div>

        <!-- Cover image -->
        <div class="space-y-1.5">
            <Label>Cover Image</Label>
            <CoverImageUpload
                v-model="form.featured_image"
                :existing-image-url="existingImageUrl"
            />
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
                        <SelectItem value="published">Published</SelectItem>
                        <SelectItem value="unpublished">Unpublished</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="form.errors.status" class="text-sm text-destructive">
                    {{ form.errors.status }}
                </p>
            </div>

            <div class="space-y-1.5">
                <Label for="scheduled_at">Schedule At</Label>
                <Input
                    id="scheduled_at"
                    v-model="form.scheduled_at"
                    type="datetime-local"
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
            <p v-if="form.errors.category_ids" class="text-sm text-destructive">
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
                    <Input
                        id="meta_title"
                        v-model="form.meta_title"
                        type="text"
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="meta_description">Meta Description</Label>
                    <Textarea
                        id="meta_description"
                        v-model="form.meta_description"
                        rows="2"
                        class="resize-none"
                    />
                </div>
            </div>
        </details>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <Button type="button" variant="outline" as-child>
                <Link :href="postsIndex().url">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
