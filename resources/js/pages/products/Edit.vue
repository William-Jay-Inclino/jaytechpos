<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import ProductSuccessModal from '@/components/modals/ProductSuccessModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    { title: 'Products', href: '/products' },
    { title: 'Edit Product', href: `/products/${props.product.id}/edit` },
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
</script>

<template>
    <Head title="Edit Product" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1
                    class="text-lg font-semibold tracking-tight sm:text-xl lg:text-2xl"
                >
                    Edit Product
                </h1>
            </div>

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
                            <Label for="category_id">Category</Label>
                            <Select v-model="form.category_id" required>
                                <SelectTrigger
                                    class="dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <SelectValue
                                        placeholder="Select category"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="form.errors.category_id"
                                class="mt-1"
                            />
                        </div>

                        <!-- Unit -->
                        <div class="grid gap-2">
                            <Label for="unit_id">Unit</Label>
                            <Select v-model="form.unit_id" required>
                                <SelectTrigger
                                    class="dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <SelectValue placeholder="Select unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="unit in units"
                                        :key="unit.id"
                                        :value="unit.id.toString()"
                                    >
                                        {{ unit.unit_name }} ({{
                                            unit.abbreviation
                                        }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
                        <Label for="status">Status</Label>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Active = available for sale, Inactive = out of stock or not available
                        </p>
                        <Select v-model="form.status" required>
                            <SelectTrigger
                                class="dark:border-gray-700 dark:bg-gray-800"
                            >
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive"
                                    >Inactive</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="form.errors.status"
                            class="mt-1"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Updating...' : 'Update Product' }}
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
    </AppLayout>
</template>