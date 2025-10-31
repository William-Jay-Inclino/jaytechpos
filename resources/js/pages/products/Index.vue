<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { showConfirmDelete } from '@/lib/swal';
import { showSuccessToast } from '@/lib/toast';
import { Product, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { LucideEdit, LucideTrash } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import axios from 'axios';
import { showErrorAlert } from '@/lib/swal';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
];

const props = defineProps<{
    products: Product[];
}>();

// Search functionality
const search = ref('');

// Filter products based on search term
const filteredProducts = computed(() => {
    if (!search.value.trim()) {
        return props.products;
    }

    const searchTerm = search.value.toLowerCase().trim();

    return props.products.filter((product) => {
        const productName = product.product_name?.toLowerCase() || '';
        const categoryName =
            product.product_category?.name?.toLowerCase() || '';
        const status = product.status?.toLowerCase() || '';
        const unitName = product.unit?.unit_name?.toLowerCase() || '';

        return (
            productName.includes(searchTerm) ||
            categoryName.includes(searchTerm) ||
            status.includes(searchTerm) ||
            unitName.includes(searchTerm)
        );
    });
});

// Status badge styling
function getStatusClass(status: string) {
    return status === 'active'
        ? 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        : 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
}

// Format currency
function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
}

// Action handlers
async function deleteProduct(productId: number) {
    const result = await showConfirmDelete({
        title: 'Are you sure?',
        text: 'This action cannot be undone. The product will be permanently deleted.',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/products/${productId}`);
            
            if (response.data.success) {
                showSuccessToast('Product deleted successfully!');
                // Refresh the page to update the product list
                window.location.reload();
            }
        } catch (error: any) {
            if (error.response?.status === 422) {
                // Product is referenced in sales items
                showErrorAlert({
                    title: 'Cannot Delete Product',
                    text: error.response.data.message || 'This product cannot be deleted because it has been used in sales transactions.',
                });
            } else {
                showErrorAlert({
                    title: 'Error',
                    text: 'An error occurred while deleting the product. Please try again.',
                });
            }
        }
    }
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header -->
            <div
                class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <h1
                    class="text-lg font-semibold tracking-tight sm:text-xl lg:text-2xl"
                >
                    Products
                </h1>
                <Link href="/products/create" class="w-full sm:w-auto">
                    <Button class="w-full shadow-md sm:w-auto"
                        >Add Product</Button
                    >
                </Link>
            </div>

            <!-- Search -->
            <div class="mb-6">
                <div
                    class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
                >
                    <div class="w-full sm:w-80">
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Search here..."
                            class="w-full"
                        />
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ filteredProducts.length }} of
                        {{ products.length }} products
                    </div>
                </div>
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
                                Product Name
                            </TableHead>
                            <TableHead
                                class="px-4 py-3 text-left text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Category
                            </TableHead>
                            <TableHead
                                class="px-4 py-3 text-left text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Unit
                            </TableHead>
                            <TableHead
                                class="px-4 py-3 text-right text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Unit Price
                            </TableHead>
                            <TableHead
                                class="px-4 py-3 text-right text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Cost Price
                            </TableHead>
                            <TableHead
                                class="px-4 py-3 text-center text-sm font-medium text-gray-700 sm:px-6 dark:text-gray-300"
                            >
                                Status
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
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <TableCell
                                class="px-4 py-4 font-medium text-gray-900 sm:px-6 dark:text-gray-100"
                            >
                                <div>
                                    <div class="font-medium">
                                        {{ product.product_name }}
                                    </div>
                                    <div
                                        v-if="product.description"
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ product.description }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell
                                class="px-4 py-4 text-gray-600 sm:px-6 dark:text-gray-300"
                            >
                                {{ product.product_category?.name || 'N/A' }}
                            </TableCell>
                            <TableCell
                                class="px-4 py-4 text-gray-600 sm:px-6 dark:text-gray-300"
                            >
                                {{ product.unit?.unit_name || 'N/A' }}
                                <span
                                    v-if="product.unit?.abbreviation"
                                    class="text-gray-400 dark:text-gray-500"
                                >
                                    ({{ product.unit.abbreviation }})
                                </span>
                            </TableCell>
                            <TableCell
                                class="px-4 py-4 text-right font-medium text-gray-900 sm:px-6 dark:text-gray-100"
                            >
                                {{ formatCurrency(product.unit_price) }}
                            </TableCell>
                            <TableCell
                                class="px-4 py-4 text-right text-gray-600 sm:px-6 dark:text-gray-300"
                            >
                                {{ formatCurrency(product.cost_price) }}
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center sm:px-6">
                                <span :class="getStatusClass(product.status)">
                                    {{
                                        product.status.charAt(0).toUpperCase() +
                                        product.status.slice(1)
                                    }}
                                </span>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center sm:px-6">
                                <div
                                    class="flex items-center justify-center gap-3"
                                >
                                    <Link
                                        :href="`/products/${product.id}/edit`"
                                        title="Edit product"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-500"
                                        >
                                            <LucideEdit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-500"
                                        title="Delete product"
                                        @click="deleteProduct(product.id)"
                                    >
                                        <LucideTrash class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Empty state -->
                        <TableRow v-if="filteredProducts.length === 0">
                            <TableCell
                                colspan="7"
                                class="px-4 py-12 text-center sm:px-6"
                            >
                                <div class="text-gray-500 dark:text-gray-400">
                                    <div class="mb-2 text-lg font-medium">
                                        No products found
                                    </div>
                                    <div class="text-sm">
                                        {{
                                            search
                                                ? 'Try adjusting your search terms'
                                                : 'Get started by adding your first product'
                                        }}
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
