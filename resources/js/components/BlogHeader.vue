<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { index as blogIndex } from '@/routes/blog';

withDefaults(
    defineProps<{
        maxWidth?: string;
    }>(),
    { maxWidth: 'max-w-5xl' },
);
</script>

<template>
    <header class="border-b">
        <div
            :class="[
                'mx-auto flex items-center justify-between px-4 py-4',
                maxWidth,
            ]"
        >
            <Link :href="home()" class="text-xl font-bold"
                >Chameleon Engineer</Link
            >
            <nav class="flex gap-4 text-sm">
                <Link
                    :href="home()"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                    >Home</Link
                >
                <Link
                    :href="blogIndex()"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                    >Blog</Link
                >
            </nav>
        </div>
    </header>
</template>
