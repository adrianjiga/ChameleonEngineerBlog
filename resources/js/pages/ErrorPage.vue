<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

const props = defineProps<{ status: number }>();

const titles: Record<number, string> = {
    403: 'Forbidden',
    404: 'Page Not Found',
    500: 'Server Error',
    503: 'Service Unavailable',
};

const messages: Record<number, string> = {
    403: "Sorry, you don't have permission to access this page.",
    404: "Sorry, the page you are looking for doesn't exist.",
    500: 'Whoops, something went wrong on our servers.',
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
};

const title = titles[props.status] ?? 'An Error Occurred';
const message = messages[props.status] ?? 'An unexpected error occurred.';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 text-center">
        <p class="text-muted-foreground text-8xl font-bold">{{ status }}</p>
        <div class="space-y-2">
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <p class="text-muted-foreground">{{ message }}</p>
        </div>
        <Link
            :href="home()"
            class="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium transition-colors"
        >
            Go Home
        </Link>
    </div>
</template>
