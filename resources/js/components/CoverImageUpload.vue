<script setup lang="ts">
import { ImageIcon, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    modelValue: File | null;
    existingImageUrl?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
}>();

const preview = ref<string | null>(props.existingImageUrl ?? null);
const isDragging = ref(false);
const error = ref<string | null>(null);

const MAX_SIZE_MB = 5;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function validate(file: File): string | null {
    if (!ALLOWED_TYPES.includes(file.type)) {
        return 'Only JPEG, PNG, GIF, and WebP images are allowed.';
    }
    if (file.size > MAX_SIZE_MB * 1024 * 1024) {
        return `Image must be smaller than ${MAX_SIZE_MB}MB.`;
    }
    return null;
}

function handleFile(file: File) {
    error.value = validate(file);
    if (error.value) {
        return;
    }
    preview.value = URL.createObjectURL(file);
    emit('update:modelValue', file);
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) handleFile(file);
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) handleFile(file);
}

function remove() {
    preview.value = null;
    error.value = null;
    emit('update:modelValue', null);
}
</script>

<template>
    <div class="space-y-2">
        <!-- Preview -->
        <div v-if="preview" class="relative overflow-hidden rounded-lg">
            <img
                :src="preview"
                alt="Cover image preview"
                class="w-full object-cover"
                style="max-height: 240px"
            />
            <button
                type="button"
                class="absolute top-2 right-2 rounded-full bg-background/80 p-1 backdrop-blur-sm transition-colors hover:bg-background"
                @click="remove"
            >
                <X class="size-4" />
            </button>
        </div>

        <!-- Drop zone -->
        <label
            v-else
            :class="[
                'flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-8 transition-colors',
                isDragging
                    ? 'border-primary bg-primary/5'
                    : 'border-input hover:border-primary/50 hover:bg-muted/50',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-muted"
            >
                <ImageIcon class="size-6 text-muted-foreground" />
            </div>
            <div class="text-center">
                <p class="text-sm font-medium">
                    <span class="text-primary">Click to upload</span>
                    <span class="text-muted-foreground"> or drag and drop</span>
                </p>
                <p class="text-xs text-muted-foreground">
                    JPEG, PNG, GIF, WebP — max {{ MAX_SIZE_MB }}MB
                </p>
            </div>
            <Upload class="size-4 text-muted-foreground" />
            <input
                type="file"
                accept="image/jpeg,image/png,image/gif,image/webp"
                class="sr-only"
                @change="onFileChange"
            />
        </label>

        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
