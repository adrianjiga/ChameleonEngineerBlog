<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, FileText, Tag } from 'lucide-vue-next';
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
import { formatDate } from '@/lib/date';
import { statusColor } from '@/lib/post-status';
import { dashboard } from '@/routes';
import { show as postShow } from '@/routes/posts';
import type { BreadcrumbItem, Category, Post } from '@/types';

defineProps<{
    stats: {
        totalPosts: number;
        publishedPosts: number;
        draftPosts: number;
        totalCategories: number;
        totalViews: number;
    };
    recentPosts: Post[];
    popularCategories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Posts</CardDescription>
                        <CardTitle class="text-3xl">{{
                            stats.totalPosts
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <FileText class="size-3" />
                            All posts you manage
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Published</CardDescription>
                        <CardTitle class="text-3xl">{{
                            stats.publishedPosts
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Live on the blog
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Drafts</CardDescription>
                        <CardTitle class="text-3xl">{{
                            stats.draftPosts
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">In progress</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Categories</CardDescription>
                        <CardTitle class="text-3xl">{{
                            stats.totalCategories
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Tag class="size-3" />
                            Content categories
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Views</CardDescription>
                        <CardTitle class="text-3xl">{{
                            stats.totalViews
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Eye class="size-3" />
                            All-time page views
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
                                    <TableCell
                                        colspan="3"
                                        class="text-center text-muted-foreground"
                                    >
                                        No posts yet
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="post in recentPosts"
                                    :key="post.id"
                                >
                                    <TableCell class="font-medium">
                                        <Link
                                            :href="postShow(post)"
                                            class="hover:underline"
                                        >
                                            {{ post.title }}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="
                                                statusColor[post.status] ??
                                                'outline'
                                            "
                                        >
                                            {{ post.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ formatDate(post.created_at) }}
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
                        <CardDescription
                            >Most used content categories</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Category</TableHead>
                                    <TableHead class="text-right"
                                        >Posts</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="popularCategories.length === 0">
                                    <TableCell
                                        colspan="2"
                                        class="text-center text-muted-foreground"
                                    >
                                        No categories yet
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="category in popularCategories"
                                    :key="category.id"
                                >
                                    <TableCell class="font-medium">{{
                                        category.name
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        category.posts_count ?? 0
                                    }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
