<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Tag } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { show as postShow } from '@/routes/posts';
import type { BreadcrumbItem, Category, Post } from '@/types';

defineProps<{
    stats: {
        totalPosts: number;
        publishedPosts: number;
        draftPosts: number;
        totalCategories: number;
    };
    recentPosts: Post[];
    popularCategories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const statusColor: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    unpublished: 'outline',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Posts</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.totalPosts }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-muted-foreground flex items-center gap-1 text-xs">
                            <FileText class="size-3" />
                            All posts you manage
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Published</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.publishedPosts }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-muted-foreground text-xs">Live on the blog</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Drafts</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.draftPosts }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-muted-foreground text-xs">In progress</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Categories</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.totalCategories }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-muted-foreground flex items-center gap-1 text-xs">
                            <Tag class="size-3" />
                            Content categories
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Recent Posts -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Posts</CardTitle>
                        <CardDescription>Your latest content</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="recentPosts.length === 0">
                                    <TableCell colspan="3" class="text-muted-foreground text-center">
                                        No posts yet
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="post in recentPosts" :key="post.id">
                                    <TableCell class="font-medium">
                                        <Link
                                            :href="postShow({ id: post.id })"
                                            class="hover:underline"
                                        >
                                            {{ post.title }}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="statusColor[post.status] ?? 'outline'">
                                            {{ post.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ new Date(post.created_at).toLocaleDateString() }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Popular Categories -->
                <Card>
                    <CardHeader>
                        <CardTitle>Popular Categories</CardTitle>
                        <CardDescription>Most used content categories</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Category</TableHead>
                                    <TableHead class="text-right">Posts</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="popularCategories.length === 0">
                                    <TableCell colspan="2" class="text-muted-foreground text-center">
                                        No categories yet
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="category in popularCategories" :key="category.id">
                                    <TableCell class="font-medium">{{ category.name }}</TableCell>
                                    <TableCell class="text-right">{{ category.posts_count ?? 0 }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
