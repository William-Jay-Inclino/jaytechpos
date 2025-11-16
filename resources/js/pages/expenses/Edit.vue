<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

interface ExpenseCategory {
    id: number;
    name: string;
}

interface Expense {
    id: number;
    name: string;
    category_id: number;
    amount: number;
    expense_date: string;
    category?: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    expense: Expense;
    categories: ExpenseCategory[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: '/expenses' },
    { title: 'Edit Expense', href: `/expenses/${props.expense.id}/edit` },
];

const form = useForm({
    name: props.expense.name || '',
    category_id: props.expense.category_id?.toString() || '',
    amount: props.expense.amount?.toString() || '',
    expense_date: props.expense.expense_date || '',
});

const isSubmitting = ref(false);

// Search functionality
const categorySearch = ref('');
const showCategoryDropdown = ref(false);

// Filtered options
const filteredCategories = computed(() => {
    if (!categorySearch.value) return props.categories;
    return props.categories.filter(category =>
        category.name.toLowerCase().includes(categorySearch.value.toLowerCase())
    );
});

// Get selected category name for display
const selectedCategoryName = computed(() => {
    const category = props.categories.find(c => c.id.toString() === form.category_id);
    return category?.name || '';
});

// Functions to handle selection
function selectCategory(categoryId: string) {
    form.category_id = categoryId;
    showCategoryDropdown.value = false;
    categorySearch.value = '';
}

async function submit() {
    if (isSubmitting.value) return;
    
    // Clear previous errors
    form.clearErrors();
    isSubmitting.value = true;
    
    try {
        const formData = {
            name: form.name,
            category_id: parseInt(form.category_id),
            amount: parseFloat(form.amount),
            expense_date: form.expense_date,
        };

        const response = await axios.put(`/expenses/${props.expense.id}`, formData);
        
        if (response.data.success) {
            showSuccessToast('Expense updated successfully!');
            // Redirect to expenses listing
            router.visit('/expenses');
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
            console.error('Error updating expense:', error);
        }
    } finally {
        isSubmitting.value = false;
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
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Edit Expense" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">

            <div class="mx-auto max-w-2xl">

                <!-- Form Card -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Expense Name -->
                        <div class="grid gap-2">
                            <Label for="name">Expense Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- Category -->
                        <div class="grid gap-2">
                            <Label for="category_id">Category</Label>
                            <div class="relative category-dropdown">
                                <div
                                    @click="showCategoryDropdown = !showCategoryDropdown"
                                    class="flex h-10 w-full cursor-pointer items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <span class="truncate">
                                        {{ selectedCategoryName || 'Select category...' }}
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
                                        <Input
                                            v-model="categorySearch"
                                            placeholder="Search categories..."
                                            class="h-8 w-full border-0 px-0 focus:ring-0"
                                        />
                                    </div>
                                    <div class="max-h-40 overflow-auto">
                                        <div
                                            v-for="category in filteredCategories"
                                            :key="category.id"
                                            @click="selectCategory(category.id.toString())"
                                            class="flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                                        >
                                            {{ category.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.category_id" class="mt-1" />
                        </div>

                        <!-- Amount -->
                        <div class="grid gap-2">
                            <Label for="amount">Amount (₱)</Label>
                            <Input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="form.errors.amount" class="mt-1" />
                        </div>
                        
                        <!-- Expense Date -->
                        <div class="grid gap-2">
                            <Label for="expense_date">Expense Date</Label>
                            <Input
                                id="expense_date"
                                v-model="form.expense_date"
                                type="date"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="form.errors.expense_date" class="mt-1" />
                        </div>
    
                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Saving Expense...' : 'Save Expense' }}
                            </Button>
                            <Link href="/expenses">
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </AppLayout>
</template>