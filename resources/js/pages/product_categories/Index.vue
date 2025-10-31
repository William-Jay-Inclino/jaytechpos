<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { showConfirmDelete, showSuccessToast } from '@/lib';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LucideEdit, LucideTrash } from 'lucide-vue-next';

defineProps<{
    product_categories: Array<any>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product Categories',
        href: '/product-categories',
    },
];

const form = useForm({});

async function onClickDelete(categoryId: number) {
    const result = await showConfirmDelete({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        form.delete(`/product-categories/${categoryId}`, {
            preserveScroll: true,
            onSuccess: () => {
                showSuccessToast('Category deleted successfully!');
            },
        });
    }
}
</script>

<template>
    <Head title="Product Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header -->
            <div
                class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <h1
                    class="text-lg font-semibold tracking-tight sm:text-xl lg:text-2xl"
                >
                    Product Categories
                </h1>
                <Link
                    href="/product-categories/create"
                    class="w-full sm:w-auto"
                >
                    <Button class="w-full shadow-md sm:w-auto"
                        >Add Product Category</Button
                    >
                </Link>
            </div>

            <!-- Table -->
            <div
                class="overflow-x-auto rounded-xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <Table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <TableHeader>
                        <TableRow class="bg-gray-50 dark:bg-gray-800">
                            <TableHead
                                class="px-4 py-3 text-left text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Name
                            </TableHead>
                            <TableHead
                                class="px-4 py-3 text-left text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Description
                            </TableHead>
                            <TableHead
                                class="w-32 px-4 py-3 text-center text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="category in product_categories"
                            :key="category.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <TableCell
                                class="px-4 py-4 font-medium text-gray-800 sm:px-6 dark:text-gray-100"
                            >
                                {{ category.name }}
                            </TableCell>
                            <TableCell
                                class="px-4 py-4 text-gray-600 sm:px-6 dark:text-gray-300"
                            >
                                {{ category.description }}
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center sm:px-6">
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <Link
                                        :href="`/product-categories/${category.id}/edit`"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-500"
                                        >
                                            <lucide-edit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        @click="onClickDelete(category.id)"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-500"
                                    >
                                        <lucide-trash class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
