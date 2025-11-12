<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import ProductSuccessModal from '@/components/modals/ProductSuccessModal.vue';
import ProductCategoryModal from '@/components/modals/ProductCategoryModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import AppLayout from '@/layouts/AppLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Settings, Search } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

interface Category {
    id: number;
    name: string;
}

interface Unit {
    id: number;
    unit_name: string;
    abbreviation: string;
}

interface Product {
    id: number;
    product_name: string;
    description?: string | null;
    category_id: number;
    unit_id: number;
    unit_price: number;
    cost_price: number;
    status: string;
    product_category?: {
        id: number;
        name: string;
    };
    unit?: {
        id: number;
        unit_name: string;
        abbreviation: string;
    };
}

const props = defineProps<{
    product: Product;
    categories: Category[];
    units: Unit[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    // { title: 'Products', href: '/products' },
    // { title: 'Edit Product', href: `/products/${props.product.id}/edit` },
];

const form = useForm({
    product_name: props.product.product_name || '',
    category_id: props.product.category_id?.toString() || '',
    unit_id: props.product.unit_id?.toString() || '',
    unit_price: props.product.unit_price?.toString() || '',
    cost_price: props.product.cost_price?.toString() || '',
    status: props.product.status || 'active',
});

const showSuccessModal = ref(false);
const updatedProduct = ref(null);
const isSubmitting = ref(false);
const showCategoryModal = ref(false);
const categories = ref<Category[]>([...props.categories]);

// Search functionality
const categorySearch = ref('');
const unitSearch = ref('');
const showCategoryDropdown = ref(false);
const showUnitDropdown = ref(false);

// Filtered options
const filteredCategories = computed(() => {
    if (!categorySearch.value) return categories.value;
    return categories.value.filter(category =>
        category.name.toLowerCase().includes(categorySearch.value.toLowerCase())
    );
});

const filteredUnits = computed(() => {
    if (!unitSearch.value) return props.units;
    return props.units.filter(unit =>
        unit.unit_name.toLowerCase().includes(unitSearch.value.toLowerCase()) ||
        unit.abbreviation.toLowerCase().includes(unitSearch.value.toLowerCase())
    );
});

// Get selected category/unit name for display
const selectedCategoryName = computed(() => {
    const category = categories.value.find(c => c.id.toString() === form.category_id);
    return category?.name || '';
});

const selectedUnitName = computed(() => {
    const unit = props.units.find(u => u.id.toString() === form.unit_id);
    return unit ? `${unit.unit_name} (${unit.abbreviation})` : '';
});

// Functions to handle selection
function selectCategory(categoryId: string) {
    form.category_id = categoryId;
    showCategoryDropdown.value = false;
    categorySearch.value = '';
}

function selectUnit(unitId: string) {
    form.unit_id = unitId;
    showUnitDropdown.value = false;
    unitSearch.value = '';
}

async function submit() {
    if (isSubmitting.value) return;
    
    // Clear previous errors
    form.clearErrors();
    isSubmitting.value = true;
    
    try {
        const formData = {
            product_name: form.product_name,
            category_id: parseInt(form.category_id),
            unit_id: parseInt(form.unit_id),
            unit_price: parseFloat(form.unit_price),
            cost_price: parseFloat(form.cost_price),
            status: form.status,
        };

        const response = await axios.put(`/products/${props.product.id}`, formData);
        
        if (response.data.success) {
            updatedProduct.value = response.data.product;
            showSuccessModal.value = true;
            showSuccessToast('Product updated successfully!');
        }
    } catch (error: any) {
        if (error.response?.status === 422) {
            // Handle validation errors
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(key => {
                if (key in form.data()) {
                    form.setError(key as keyof typeof form.data, errors[key][0]);
                }
            });
        } else {
            console.error('Error updating product:', error);
        }
    } finally {
        isSubmitting.value = false;
    }
}

function onModalClose() {
    showSuccessModal.value = false;
    // Redirect to products listing after modal closes
    router.visit('/products');
}

async function handleCategoryUpdated() {
    // Refresh only active categories without page reload
    try {
        const response = await axios.get('/api/product-categories/active');
        if (response.data.success) {
            categories.value = response.data.categories;
        }
    } catch (error) {
        console.error('Error refreshing categories:', error);
    }
}

// Click outside handler
function handleClickOutside(event: Event) {
    const target = event.target as Element;
    
    // Check if click is outside category dropdown
    const categoryDropdown = document.querySelector('.category-dropdown');
    if (categoryDropdown && !categoryDropdown.contains(target)) {
        showCategoryDropdown.value = false;
        categorySearch.value = '';
    }
    
    // Check if click is outside unit dropdown
    const unitDropdown = document.querySelector('.unit-dropdown');
    if (unitDropdown && !unitDropdown.contains(target)) {
        showUnitDropdown.value = false;
        unitSearch.value = '';
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Edit Product" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">

            <!-- Form Card -->
            <div
                class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
            >
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Product Name -->
                    <div class="grid gap-2">
                        <Label for="product_name">Product Name</Label>
                        <Input
                            id="product_name"
                            v-model="form.product_name"
                            type="text"
                            required
                            class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                        <InputError
                            :message="form.errors.product_name"
                            class="mt-1"
                        />
                    </div>

                    <!-- Category and Unit Row -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Category -->
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="category_id">Category</Label>
                                <Button 
                                    @click="showCategoryModal = true" 
                                    variant="outline" 
                                    size="sm" 
                                    type="button"
                                    class="gap-1 text-xs"
                                >
                                    <Settings class="h-3 w-3" />
                                    Manage Categories
                                </Button>
                            </div>
                            <div class="relative category-dropdown">
                                <div
                                    @click="showCategoryDropdown = !showCategoryDropdown"
                                    class="flex h-10 w-full cursor-pointer items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <span class="truncate">
                                        {{ selectedCategoryName || 'Select category' }}
                                    </span>
                                    <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                                
                                <div
                                    v-if="showCategoryDropdown"
                                    class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                        <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                        <input
                                            v-model="categorySearch"
                                            placeholder="Search categories..."
                                            class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                    </div>
                                    <div class="max-h-40 overflow-auto">
                                        <div
                                            v-for="category in filteredCategories"
                                            :key="category.id"
                                            @click="selectCategory(category.id.toString())"
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                        >
                                            {{ category.name }}
                                        </div>
                                        <div
                                            v-if="filteredCategories.length === 0"
                                            class="py-6 text-center text-sm text-muted-foreground"
                                        >
                                            No categories found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError
                                :message="form.errors.category_id"
                                class="mt-1"
                            />
                        </div>

                        <!-- Unit -->
                        <div class="grid gap-2">
                            <Label for="unit_id">Unit</Label>
                            <div class="relative unit-dropdown">
                                <div
                                    @click="showUnitDropdown = !showUnitDropdown"
                                    class="flex h-10 w-full cursor-pointer items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <span class="truncate">
                                        {{ selectedUnitName || 'Select unit' }}
                                    </span>
                                    <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                                
                                <div
                                    v-if="showUnitDropdown"
                                    class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                        <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                        <input
                                            v-model="unitSearch"
                                            placeholder="Search units..."
                                            class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                    </div>
                                    <div class="max-h-40 overflow-auto">
                                        <div
                                            v-for="unit in filteredUnits"
                                            :key="unit.id"
                                            @click="selectUnit(unit.id.toString())"
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                        >
                                            {{ unit.unit_name }} ({{ unit.abbreviation }})
                                        </div>
                                        <div
                                            v-if="filteredUnits.length === 0"
                                            class="py-6 text-center text-sm text-muted-foreground"
                                        >
                                            No units found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError
                                :message="form.errors.unit_id"
                                class="mt-1"
                            />
                        </div>
                    </div>

                    <!-- Price Row -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Unit Price -->
                        <div class="grid gap-2">
                            <Label for="unit_price">Unit Price (₱)</Label>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Selling price na ibebenta mo sa customer
                            </p>
                            <Input
                                id="unit_price"
                                v-model="form.unit_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError
                                :message="form.errors.unit_price"
                                class="mt-1"
                            />
                        </div>

                        <!-- Cost Price -->
                        <div class="grid gap-2">
                            <Label for="cost_price">Cost Price (₱)</Label>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Puhunan mo o presyong nabili mo sa supplier
                            </p>
                            <Input
                                id="cost_price"
                                v-model="form.cost_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError
                                :message="form.errors.cost_price"
                                class="mt-1"
                            />
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="grid gap-2">
                        <Label>Status</Label>
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <button
                                    type="button"
                                    @click="form.status = form.status === 'active' ? 'inactive' : 'active'"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                        form.status === 'active' 
                                            ? 'bg-green-500' 
                                            : 'bg-gray-300 dark:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                                            form.status === 'active' 
                                                ? 'translate-x-6' 
                                                : 'translate-x-1'
                                        ]"
                                    ></span>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <span 
                                    :class="[
                                        'text-sm font-medium',
                                        form.status === 'active' 
                                            ? 'text-green-600 dark:text-green-400' 
                                            : 'text-gray-600 dark:text-gray-400'
                                    ]"
                                >
                                    {{ form.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                                <span 
                                    :class="[
                                        'text-xs px-2 py-1 rounded-full',
                                        form.status === 'active' 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' 
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                                    ]"
                                >
                                    {{ form.status === 'active' ? 'Available for sale' : 'Not available' }}
                                </span>
                            </div>
                        </div>
                        <InputError
                            :message="form.errors.status"
                            class="mt-1"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Saving Product...' : 'Save Product' }}
                        </Button>
                        <Link href="/products">
                            <Button variant="outline" type="button">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </div>

        <!-- Success Modal -->
        <ProductSuccessModal 
            v-model:open="showSuccessModal" 
            :product="updatedProduct"
            mode="edit"
            @update:open="onModalClose"
        />

        <!-- Category Management Modal -->
        <ProductCategoryModal 
            v-model:open="showCategoryModal"
            @category-updated="handleCategoryUpdated"
        />
    </AppLayout>
</template>