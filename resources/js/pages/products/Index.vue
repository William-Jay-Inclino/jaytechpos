<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Product, ProductCategory, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, withDefaults, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Search } from 'lucide-vue-next';

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
const statusFilter = ref('all'); // 'all', 'active', 'inactive'

// Category search state
const categorySearch = ref('');
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

// Filtered categories based on search
const filteredCategories = computed(() => {
    if (!categorySearch.value) return props.categories;
    return props.categories.filter(category =>
        category.name.toLowerCase().includes(categorySearch.value.toLowerCase())
    );
});

const selectedCategoryName = computed(() => {
    if (categoryFilter.value === 'all') return 'All Categories';
    const category = props.categories.find(c => c.id.toString() === categoryFilter.value);
    return category ? category.name : '';
});

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
};

// Helper functions for category selection
function selectCategory(categoryId: string) {
    categoryFilter.value = categoryId;
    showCategoryDropdown.value = false;
    categorySearch.value = '';
}

// Helper function for status filter
function setStatusFilter(status: string) {
    statusFilter.value = status;
}

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
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Button as-child>
                        <Link href="/products/create">
                            ➕ Add New Product
                        </Link>
                    </Button>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ totalProducts }} products
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-4">
                        <!-- Search row -->
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex-1">
                                <Input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search product name"
                                    class="w-full"
                                />
                            </div>
                        </div>
                        
                        <!-- Bottom row: Filters -->
                        <div class="flex flex-col gap-4 sm:flex-row">
                            <div class="flex-1 sm:max-w-[250px] relative category-dropdown">
                                <div
                                    @click="showCategoryDropdown = !showCategoryDropdown"
                                    class="flex h-10 w-full cursor-pointer items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <span class="truncate text-left">
                                        {{ selectedCategoryName || 'Filter by category' }}
                                    </span>
                                    <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                                
                                <div
                                    v-if="showCategoryDropdown"
                                    class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                        <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                        <input
                                            v-model="categorySearch"
                                            placeholder="Search categories..."
                                            class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                                            @click.stop
                                        />
                                    </div>
                                    <div class="max-h-40 overflow-auto">
                                        <!-- All Categories Option -->
                                        <div
                                            @click="selectCategory('all')"
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            :class="categoryFilter === 'all' ? 'bg-accent text-accent-foreground' : ''"
                                        >
                                            <div class="font-medium">All Categories</div>
                                        </div>
                                        
                                        <!-- Category Options -->
                                        <div
                                            v-for="category in filteredCategories"
                                            :key="category.id"
                                            @click="selectCategory(category.id.toString())"
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            :class="categoryFilter === category.id.toString() ? 'bg-accent text-accent-foreground' : ''"
                                        >
                                            <div class="font-medium">{{ category.name }}</div>
                                        </div>
                                        
                                        <div
                                            v-if="filteredCategories.length === 0 && categorySearch.trim()"
                                            class="py-6 text-center text-sm text-muted-foreground"
                                        >
                                            No categories found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- All Status Button -->
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="setStatusFilter('all')"
                                    :class="[
                                        'transition-colors',
                                        statusFilter === 'all'
                                            ? 'bg-blue-100 text-blue-800 border-blue-300 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700 dark:hover:bg-blue-800' 
                                            : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <span v-if="statusFilter === 'all'" class="mr-2">✓</span>
                                    All
                                </Button>
                                
                                <!-- Active Status Button -->
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="setStatusFilter('active')"
                                    :class="[
                                        'transition-colors',
                                        statusFilter === 'active'
                                            ? 'bg-green-100 text-green-800 border-green-300 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-700 dark:hover:bg-green-800' 
                                            : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <span v-if="statusFilter === 'active'" class="mr-2">✓</span>
                                    Active
                                </Button>
                                
                                <!-- Inactive Status Button -->
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="setStatusFilter('inactive')"
                                    :class="[
                                        'transition-colors',
                                        statusFilter === 'inactive'
                                            ? 'bg-red-100 text-red-800 border-red-300 hover:bg-red-200 dark:bg-red-900 dark:text-red-200 dark:border-red-700 dark:hover:bg-red-800' 
                                            : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <span v-if="statusFilter === 'inactive'" class="mr-2">✓</span>
                                    Inactive
                                </Button>
                            </div>
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

                    <div v-else-if="filteredProducts.length === 0 && (searchQuery.trim() || categoryFilter !== 'all' || statusFilter !== 'all')" class="px-6 py-12 text-center">
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
