<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    destroy,
    store,
    update,
} from '@/actions/App/Http/Controllers/CategoryController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { index as categoriesIndex } from '@/routes/categories';
import type { BreadcrumbItem, Category } from '@/types';

defineProps<{
    categories: (Category & { can: { update: boolean; delete: boolean } })[];
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Categories', href: categoriesIndex().url },
];

// Create dialog
const createOpen = ref(false);
const createForm = useForm({ name: '', description: '' });

function submitCreate() {
    createForm.submit(store(), {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

// Edit dialog
const editOpen = ref(false);
const editingCategory = ref<Category | null>(null);
const editForm = useForm({ name: '', description: '' });

function openEdit(category: Category) {
    editingCategory.value = category;
    editForm.name = category.name;
    editForm.description = category.description ?? '';
    editOpen.value = true;
}

function submitEdit() {
    if (!editingCategory.value) return;
    editForm.submit(update({ category: editingCategory.value.id }), {
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

const deleteForm = useForm({});

function confirmDelete(category: Category) {
    if (!confirm(`Delete "${category.name}"? This cannot be undone.`)) return;
    deleteForm.submit(destroy({ category: category.id }));
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Categories</h1>

                <Dialog v-if="can.create" v-model:open="createOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="size-4" />
                            New Category
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create Category</DialogTitle>
                        </DialogHeader>
                        <form class="space-y-4" @submit.prevent="submitCreate">
                            <div class="space-y-1.5">
                                <Label for="create-name"
                                    >Name
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    type="text"
                                    placeholder="Category name"
                                />
                                <p
                                    v-if="createForm.errors.name"
                                    class="text-sm text-destructive"
                                >
                                    {{ createForm.errors.name }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="create-description"
                                    >Description</Label
                                >
                                <Textarea
                                    id="create-description"
                                    v-model="createForm.description"
                                    rows="2"
                                    placeholder="Optional description"
                                    class="resize-none"
                                />
                            </div>
                            <DialogFooter>
                                <Button
                                    type="submit"
                                    :disabled="createForm.processing"
                                >
                                    {{
                                        createForm.processing
                                            ? 'Creating...'
                                            : 'Create'
                                    }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead class="text-right">Posts</TableHead>
                            <TableHead class="w-20" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="categories.length === 0">
                            <TableCell
                                colspan="4"
                                class="py-10 text-center text-muted-foreground"
                            >
                                No categories yet.
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="category in categories"
                            :key="category.id"
                        >
                            <TableCell class="font-medium">{{
                                category.name
                            }}</TableCell>
                            <TableCell class="max-w-xs text-muted-foreground">
                                <span class="line-clamp-1">{{
                                    category.description ?? '—'
                                }}</span>
                            </TableCell>
                            <TableCell class="text-right">{{
                                category.posts_count ?? 0
                            }}</TableCell>
                            <TableCell>
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="category.can.update"
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Edit category"
                                        @click="openEdit(category)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="category.can.delete"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        :disabled="deleteForm.processing"
                                        aria-label="Delete category"
                                        @click="confirmDelete(category)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Edit Dialog (outside table for proper portal rendering) -->
        <Dialog v-model:open="editOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Category</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitEdit">
                    <div class="space-y-1.5">
                        <Label for="edit-name"
                            >Name <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            type="text"
                        />
                        <p
                            v-if="editForm.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ editForm.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="edit-description">Description</Label>
                        <Textarea
                            id="edit-description"
                            v-model="editForm.description"
                            rows="2"
                            class="resize-none"
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="editForm.processing">
                            {{
                                editForm.processing
                                    ? 'Saving...'
                                    : 'Save Changes'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
