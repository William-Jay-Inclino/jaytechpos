<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Product, ProductCategory, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, withDefaults, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Search, Edit, Trash2 } from 'lucide-vue-next';

// UI Components
import { Badge } from '@/components/ui/badge';
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
const statusFilter = ref('all'); // 'all', 'active', 'inactive'

const showCategoryDropdown = ref(false);

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

// Computed properties
const selectedCategoryName = computed(() => {
    if (categoryFilter.value === 'all') return 'All Categories';
    const category = props.categories.find(c => c.id.toString() === categoryFilter.value);
    return category ? category.name : '';
});

// Helper functions
const formatCurrency = (amount: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);

const selectCategory = (categoryId: string) => {
    categoryFilter.value = categoryId;
    showCategoryDropdown.value = false;
};

const setStatusFilter = (status: string) => {
    statusFilter.value = status;
};



// Handle click outside to close dropdowns
function handleClickOutside(event: MouseEvent) {
    const categoryDropdown = document.querySelector('.category-dropdown');
    if (categoryDropdown && !categoryDropdown.contains(event.target as Node)) {
        showCategoryDropdown.value = false;
    }
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

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <!-- Page Header -->
            <div class="mx-auto max-w-7xl">
                <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
                    
                    <Button as-child class="w-full sm:w-auto">
                        <Link href="/products/create" class="flex items-center justify-center gap-2">
                            <span>Add Product</span>
                        </Link>
                    </Button>
                </div>

                <!-- Search and Filters -->
                <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search here..."
                                class="pl-10"
                            />
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="relative category-dropdown sm:w-48">
                            <div
                                @click="showCategoryDropdown = !showCategoryDropdown"
                                class="flex h-10 w-full cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-background px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-500"
                            >
                                <span class="truncate">{{ selectedCategoryName }}</span>
                                <svg class="h-4 w-4 opacity-50" :class="showCategoryDropdown && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </div>
                            
                            <div v-if="showCategoryDropdown" class="absolute z-50 mt-1 w-full rounded-lg border bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800 p-1">
                                <div @click="selectCategory('all')" class="rounded px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer" :class="categoryFilter === 'all' && 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'">
                                    All Categories
                                </div>
                                <div v-for="category in categories" :key="category.id" @click="selectCategory(category.id.toString())" class="rounded px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer" :class="categoryFilter === category.id.toString() && 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'">
                                    {{ category.name }}
                                </div>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex gap-2">
                            <button v-for="status in [
                                { key: 'all', label: 'All', count: totalProducts, color: 'blue' },
                                { key: 'active', label: 'Active', count: activeProducts, color: 'green' },
                                { key: 'inactive', label: 'Inactive', count: inactiveProducts, color: 'red' }
                            ]" :key="status.key" @click="setStatusFilter(status.key)" :class="[
                                'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                statusFilter === status.key
                                    ? `bg-${status.color}-600 text-white`
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                            ]">
                                {{ status.label }} ({{ status.count }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Products Display -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <!-- Empty State - No Products -->
                    <div v-if="!Array.isArray(products) || products.length === 0" class="px-4 py-16 sm:px-6 sm:py-20 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl">📦</span>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No products yet
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Get started by adding your first product to manage your inventory.
                        </p>
                        <div class="mt-8">
                            <Button as-child size="lg">
                                <Link href="/products/create" class="flex items-center gap-2">
                                    <span class="text-lg">+</span>
                                    Add Your First Product
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State - No Results -->
                    <div v-else-if="filteredProducts.length === 0 && (searchQuery.trim() || categoryFilter !== 'all' || statusFilter !== 'all')" class="px-4 py-16 sm:px-6 sm:py-20 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <Search class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No products found
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Try adjusting your search terms or filter criteria to find what you're looking for.
                        </p>
                        <div class="mt-8">
                            <Button variant="outline" @click="searchQuery = ''; categoryFilter = 'all'; statusFilter = 'all'" size="lg">
                                Clear All Filters
                            </Button>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div v-else>

                        <!-- Mobile Cards -->
                        <div class="block lg:hidden divide-y divide-gray-100 dark:divide-gray-700">
                            <div
                                v-for="product in filteredProducts"
                                :key="product.id"
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors space-y-3"
                            >
                                <!-- Header Row -->
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                            {{ product.product_name }}
                                        </h3>
                                        <p v-if="product.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                                            {{ product.description }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <Badge 
                                                variant="outline"
                                                class="text-xs bg-gray-50 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                            >
                                                {{ product.product_category?.name || 'Uncategorized' }}
                                            </Badge>
                                            <Badge 
                                                :variant="product.status === 'active' ? 'default' : 'destructive'"
                                                :class="product.status === 'active' ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-800' : ''"
                                                class="text-xs"
                                            >
                                                {{ product.status === 'active' ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prices Row -->
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col gap-1">
                                        <div>
                                            <span class="text-xs text-gray-500">Unit Price: </span>
                                            <span class="font-bold text-green-600 dark:text-green-400">
                                                {{ formatCurrency(product.unit_price) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Cost Price: </span>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ formatCurrency(product.cost_price) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions Row -->
                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        as-child
                                        class="h-8 px-3"
                                    >
                                        <Link :href="`/products/${product.id}/edit`">
                                            <Edit class="h-3 w-3 mr-1.5" />
                                            <span class="hidden sm:inline">Edit</span>
                                        </Link>
                                    </Button>
                                    
                                    <Button 
                                        size="sm" 
                                        variant="destructive"
                                        @click="deleteProduct(product.id)"
                                        class="h-8 px-3"
                                    >
                                        <Trash2 class="h-3 w-3 mr-1.5" />
                                        <span class="hidden sm:inline">Delete</span>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden lg:block">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-3 pl-6 pr-4 text-left text-sm font-medium text-gray-900 dark:text-white">
                                            Product
                                        </th>
                                        <th class="py-3 px-4 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            Prices
                                        </th>
                                        <th class="py-3 pl-4 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="product in filteredProducts" 
                                        :key="product.id"
                                        class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <td class="py-4 pl-6 pr-4">
                                            <div class="flex items-center gap-3">
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    {{ product.product_name }}
                                                </h4>
                                                <span v-if="product.unit?.unit_name" class="text-sm text-gray-500 dark:text-gray-400">
                                                    per {{ product.unit.unit_name }}
                                                </span>
                                            </div>
                                            <p v-if="product.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ product.description.length > 60 ? product.description.substring(0, 60) + '...' : product.description }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <Badge 
                                                    variant="outline"
                                                    class="text-xs bg-gray-50 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                                >
                                                    {{ product.product_category?.name || 'Uncategorized' }}
                                                </Badge>
                                                <Badge 
                                                    :variant="product.status === 'active' ? 'default' : 'destructive'"
                                                    :class="product.status === 'active' ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-800' : ''"
                                                >
                                                    {{ product.status === 'active' ? 'Active' : 'Inactive' }}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <div class="flex flex-col gap-1">
                                                <div>
                                                    <span class="text-xs text-gray-500">Unit Price: </span>
                                                    <span class="font-bold text-green-600 dark:text-green-400">
                                                        {{ formatCurrency(product.unit_price) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500">Cost Price: </span>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ formatCurrency(product.cost_price) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 pl-4 pr-6">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button size="sm" variant="outline" as-child>
                                                    <Link :href="`/products/${product.id}/edit`">
                                                        <Edit class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                
                                                <Button 
                                                    size="sm" 
                                                    variant="destructive"
                                                    @click="deleteProduct(product.id)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
