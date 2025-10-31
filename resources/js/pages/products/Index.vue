<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Product, ProductCategory, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, withDefaults } from 'vue';
import axios from 'axios';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import { showConfirmDelete } from '@/lib/swal';
import { showSuccessToast } from '@/lib/toast';
import { showErrorAlert } from '@/lib/swal';

const props = withDefaults(defineProps<{
    products: Array<Product>;
    categories: Array<ProductCategory>;
}>(), {
    products: () => [],
    categories: () => []
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
];

// Search and filter state
const searchQuery = ref('');
const categoryFilter = ref('all');
const statusFilter = ref('all');

// Filtered products based on search and filters
const filteredProducts = computed(() => {
    if (!Array.isArray(props.products)) return [];
    
    let filtered = props.products;
    
    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        filtered = filtered.filter(product => 
            product.product_name.toLowerCase().includes(query) ||
            (product.unit?.unit_name && product.unit.unit_name.toLowerCase().includes(query)) ||
            (product.product_category?.name && product.product_category.name.toLowerCase().includes(query))
        );
    }
    
    // Apply category filter
    if (categoryFilter.value !== 'all') {
        filtered = filtered.filter(product => 
            product.category_id === parseInt(categoryFilter.value)
        );
    }
    
    // Apply status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(product => product.status === statusFilter.value);
    }
    
    return filtered;
});

// Computed properties
const activeProducts = computed(() => 
    Array.isArray(props.products) ? props.products.filter(product => product.status === 'active').length : 0
);

const inactiveProducts = computed(() => 
    Array.isArray(props.products) ? props.products.filter(product => product.status === 'inactive').length : 0
);

const totalProducts = computed(() => Array.isArray(props.products) ? props.products.length : 0);

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
};

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
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <!-- Page Header -->
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white"
                        >
                            📦 Products
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Manage your product inventory and pricing
                        </p>
                    </div>
                    
                    <Button as-child>
                        <Link href="/products/create">
                            ➕ Add New Product
                        </Link>
                    </Button>
                </div>

                <!-- Stats Cards -->
                <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">📦</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total Products
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ totalProducts }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">✅</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Active Products
                                </p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ activeProducts }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">❌</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Inactive Products
                                </p>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ inactiveProducts }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex-1 sm:min-w-[300px]">
                                <Input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search product name"
                                    class="w-full"
                                />
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 sm:flex sm:gap-4">
                                <div class="sm:min-w-[180px]">
                                    <Select v-model="categoryFilter">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Filter by category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Categories</SelectItem>
                                            <SelectItem 
                                                v-for="category in categories" 
                                                :key="category.id" 
                                                :value="category.id.toString()"
                                            >
                                                {{ category.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                
                                <div class="sm:min-w-[150px]">
                                    <Select v-model="statusFilter">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Filter by status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ filteredProducts.length }} of {{ totalProducts }} products
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Product List
                        </h3>
                    </div>

                    <div v-if="!Array.isArray(products) || products.length === 0" class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-2xl">📦</span>
                        </div>
                        <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
                            No products yet
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Get started by adding your first product.
                        </p>
                        <div class="mt-6">
                            <Button as-child>
                                <Link href="/products/create">
                                    ➕ Add Product
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div v-else-if="filteredProducts.length === 0 && searchQuery.trim()" class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-2xl">🔍</span>
                        </div>
                        <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
                            No products found
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Try adjusting your search or filter criteria.
                        </p>
                        <div class="mt-6">
                            <Button variant="outline" @click="searchQuery = ''; categoryFilter = 'all'; statusFilter = 'all'">
                                Clear Filters
                            </Button>
                        </div>
                    </div>

                    <Table v-else-if="filteredProducts.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Product Name</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead class="text-right">Unit Price</TableHead>
                                <TableHead class="text-right">Cost Price</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="product in filteredProducts" :key="product.id">
                                <TableCell class="font-medium">
                                    <div>
                                        <div class="font-medium">
                                            {{ product.product_name }}
                                        </div>
                                        <div
                                            v-if="product.description"
                                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            {{ product.description.length > 50 ? product.description.substring(0, 50) + '...' : product.description }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {{ product.product_category?.name || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    {{ product.unit?.unit_name || 'N/A' }}
                                    <span
                                        v-if="product.unit?.abbreviation"
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        ({{ product.unit.abbreviation }})
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <span class="font-semibold">
                                        {{ formatCurrency(product.unit_price) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ formatCurrency(product.cost_price) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="product.status === 'active' ? 'default' : 'destructive'"
                                        :class="product.status === 'active' ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-800' : ''"
                                    >
                                        {{ product.status.charAt(0).toUpperCase() + product.status.slice(1) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" as-child>
                                            <Link :href="`/products/${product.id}/edit`">
                                                ✏️ Edit
                                            </Link>
                                        </Button>
                                        
                                        <Button 
                                            size="sm" 
                                            variant="destructive"
                                            @click="deleteProduct(product.id)"
                                        >
                                            🗑️ Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
