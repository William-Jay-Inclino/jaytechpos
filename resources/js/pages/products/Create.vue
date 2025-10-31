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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

const props = defineProps<{
    categories: Category[];
    units: Unit[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
    { title: 'Create Product', href: '/products/create' },
];

const form = useForm({
    product_name: '',
    description: '',
    category_id: '',
    unit_id: '',
    unit_price: '',
    cost_price: '',
    status: 'active',
});

const showSuccessModal = ref(false);
const createdProduct = ref(null);
const isSubmitting = ref(false);

async function submit() {
    if (isSubmitting.value) return;
    
    // Clear previous errors
    form.clearErrors();
    isSubmitting.value = true;
    
    try {
        const formData = {
            product_name: form.product_name,
            description: form.description,
            category_id: parseInt(form.category_id),
            unit_id: parseInt(form.unit_id),
            unit_price: parseFloat(form.unit_price),
            cost_price: parseFloat(form.cost_price),
            status: form.status,
        };

        const response = await axios.post('/products', formData);
        
        if (response.data.success) {
            createdProduct.value = response.data.product;
            showSuccessModal.value = true;
            form.reset();
            showSuccessToast('Product created successfully!');
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
            console.error('Error creating product:', error);
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <Head title="Create Product" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1
                    class="text-lg font-semibold tracking-tight sm:text-xl lg:text-2xl"
                >
                    Create Product
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
                            <Select v-model="form.category_id">
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
                            <Select v-model="form.unit_id">
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
                        <Select v-model="form.status">
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
                            {{ isSubmitting ? 'Creating...' : 'Create Product' }}
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
            :product="createdProduct"
            mode="create"
        />
    </AppLayout>
</template>
