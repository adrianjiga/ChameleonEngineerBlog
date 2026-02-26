<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { destroy } from '@/actions/App/Http/Controllers/PostController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { create, index as postsIndex } from '@/routes/posts';
import type { BreadcrumbItem, PaginatedPosts } from '@/types';

const props = defineProps<{
    posts: PaginatedPosts;
    filters: { search: string | null; status: string | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Posts', href: postsIndex().url },
];

const search = ref(props.filters.search ?? '');

function applyFilters(status?: string) {
    router.get(
        postsIndex().url,
        {
            search: search.value || undefined,
            status: status ?? props.filters.status ?? undefined,
        },
        { preserveState: true, replace: true },
    );
}

function onStatusChange(value: string) {
    applyFilters(value === 'all' ? undefined : value);
}

const statusColor: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    unpublished: 'outline',
};

function confirmDelete(postId: number) {
    if (!confirm('Are you sure you want to delete this post?')) return;
    useForm({}).submit(destroy({ id: postId }));
}
</script>

<template>
    <Head title="Posts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Posts</h1>
                <Button as-child>
                    <Link :href="create()">
                        <Plus class="size-4" />
                        New Post
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex gap-2">
                <div class="relative flex-1 max-w-sm">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search posts..."
                        class="border-input bg-background focus:ring-ring w-full rounded-md border py-2 pr-4 pl-9 text-sm outline-none focus:ring-2"
                        @keydown.enter="applyFilters()"
                    />
                </div>
                <Select :model-value="filters.status ?? 'all'" @update:model-value="onStatusChange">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="published">Published</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="unpublished">Unpublished</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <div class="border-border rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Categories</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead class="w-20" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="posts.data.length === 0">
                            <TableCell colspan="5" class="text-muted-foreground py-10 text-center">
                                No posts found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="post in posts.data" :key="post.id">
                            <TableCell class="max-w-xs font-medium">
                                <span class="line-clamp-1">{{ post.title }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="statusColor[post.status] ?? 'outline'">
                                    {{ post.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="max-w-[160px]">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="cat in post.categories?.slice(0, 2)"
                                        :key="cat.id"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        {{ cat.name }}
                                    </Badge>
                                    <span
                                        v-if="(post.categories?.length ?? 0) > 2"
                                        class="text-muted-foreground text-xs"
                                    >
                                        +{{ (post.categories?.length ?? 0) - 2 }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ new Date(post.created_at).toLocaleDateString() }}
                            </TableCell>
                            <TableCell>
                                <div class="flex gap-1">
                                    <Button variant="ghost" size="icon-sm" as-child>
                                        <Link :href="`/posts/${post.id}/edit`">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(post.id)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <nav v-if="posts.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in posts.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'border-input bg-background hover:bg-muted border',
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="text-muted-foreground rounded-md px-3 py-1.5 text-sm"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
