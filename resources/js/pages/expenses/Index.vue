<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ExpensePieChart from '@/components/ExpensePieChart.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, withDefaults, watch } from 'vue';
import axios from 'axios';
import { ChevronLeft, ChevronRight, Calendar, Edit, Trash2 } from 'lucide-vue-next';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';


import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { showConfirmDelete } from '@/lib/swal';
import { showSuccessToast } from '@/lib/toast';
import { showErrorAlert } from '@/lib/swal';

interface ExpenseCategory {
    id: number;
    name: string;
    color: string;
}

interface Expense {
    id: number;
    name: string;
    amount: number;
    expense_date: string;
    category?: {
        id: number;
        name: string;
        color: string;
    };
}

interface ChartDataItem {
    label: string;
    amount: number;
    count: number;
    color: string;
}

const props = withDefaults(defineProps<{
    expenses: Array<Expense>;
    categories: Array<ExpenseCategory>;
    chartData: Array<ChartDataItem>;
    selectedMonth: number;
    selectedYear: number;
}>(), {
    expenses: () => [],
    categories: () => [],
    chartData: () => [],
    selectedMonth: new Date().getMonth() + 1,
    selectedYear: new Date().getFullYear(),
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses',
        href: '/expenses',
    },
];

// Date filter state
const selectedMonth = ref(props.selectedMonth);
const selectedYear = ref(props.selectedYear);
const showDateDialog = ref(false);



// All expenses without filtering since we removed search/filter
const filteredExpenses = computed(() => {
    if (!Array.isArray(props.expenses)) return [];
    return props.expenses;
});

// Computed properties
const totalExpenses = computed(() => Array.isArray(props.expenses) ? props.expenses.length : 0);

const totalAmount = computed(() => {
    if (!Array.isArray(props.expenses)) return 0;
    return props.expenses.reduce((sum, expense) => sum + expense.amount, 0);
});



// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

// Generate badge style based on category color
const getCategoryBadgeStyle = (color: string) => {
    // Convert hex to RGB
    const hex = color.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    
    // Create lighter background and border colors
    const lightBg = `rgba(${r}, ${g}, ${b}, 0.1)`;
    const lightBorder = `rgba(${r}, ${g}, ${b}, 0.3)`;
    
    return {
        backgroundColor: lightBg,
        borderColor: lightBorder,
        color: color,
    };
};

// Generate month options
const monthOptions = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

// Generate year options (current year ± 5 years)
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 11 }, (_, i) => currentYear - 5 + i);

// Get current month name
const getCurrentMonthYear = computed(() => {
    const month = monthOptions.find(m => m.value === selectedMonth.value);
    return `${month?.label} ${selectedYear.value}`;
});



// Update expenses when month/year changes
function updateExpenses() {
    router.get('/expenses', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Navigate to previous month
function goToPreviousMonth() {
    if (selectedMonth.value === 1) {
        selectedMonth.value = 12;
        selectedYear.value--;
    } else {
        selectedMonth.value--;
    }
}

// Navigate to next month
function goToNextMonth() {
    if (selectedMonth.value === 12) {
        selectedMonth.value = 1;
        selectedYear.value++;
    } else {
        selectedMonth.value++;
    }
}

// Watch for month/year changes
watch([selectedMonth, selectedYear], () => {
    updateExpenses();
});



// Action handlers
async function deleteExpense(expenseId: number) {
    const result = await showConfirmDelete({
        title: 'Are you sure?',
        text: 'This action cannot be undone. The expense will be permanently deleted.',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/expenses/${expenseId}`);
            
            if (response.data.success) {
                showSuccessToast('Expense deleted successfully!');
                // Refresh the page to update the expense list
                window.location.reload();
            }
        } catch (error: any) {
            showErrorAlert({
                title: 'Error',
                text: 'An error occurred while deleting the expense. Please try again.',
            });
        }
    }
}


</script>

<template>
    <Head title="Expenses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Add New Expense Button -->
                <div class="flex justify-start">
                    <Button as-child size="lg">
                        <Link href="/expenses/create">
                            ➕ Add New Expense
                        </Link>
                    </Button>
                </div>

                <!-- Period Selection -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="goToPreviousMonth"
                                class="h-8 w-8 p-0"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            
                            <Dialog v-model:open="showDateDialog">
                                <DialogTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        class="min-w-[140px] font-medium text-gray-900 hover:text-gray-700 dark:text-white dark:hover:text-gray-300"
                                    >
                                        <Calendar class="mr-2 h-4 w-4" />
                                        {{ getCurrentMonthYear }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Select Period</DialogTitle>
                                        <DialogDescription>
                                            Choose the month and year to view expenses for.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="grid gap-4 py-4">
                                        <div class="grid grid-cols-4 items-center gap-4">
                                            <label class="text-right font-medium">
                                                Month:
                                            </label>
                                            <select
                                                v-model="selectedMonth"
                                                class="col-span-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            >
                                                <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                                                    {{ month.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-4 items-center gap-4">
                                            <label class="text-right font-medium">
                                                Year:
                                            </label>
                                            <select
                                                v-model="selectedYear"
                                                class="col-span-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            >
                                                <option v-for="year in yearOptions" :key="year" :value="year">
                                                    {{ year }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <Button @click="showDateDialog = false">
                                            Apply
                                        </Button>
                                    </div>
                                </DialogContent>
                            </Dialog>
                            
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="goToNextMonth"
                                class="h-8 w-8 p-0"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Pie Chart Section -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Expense Breakdown by Category
                        </h2>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total: {{ formatCurrency(totalAmount) }}
                        </div>
                    </div>
                    <ExpensePieChart :data="chartData" />
                </div>



                <!-- Expenses List -->
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Expenses for {{ getCurrentMonthYear }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ totalExpenses }} expenses
                        </p>
                    </div>

                    <div v-if="!Array.isArray(expenses) || expenses.length === 0" class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl">💰</span>
                        </div>
                        <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">
                            No expenses yet
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Get started by adding your first expense for this period.
                        </p>
                        <div class="mt-6">
                            <Button as-child>
                                <Link href="/expenses/create">
                                    Add Expense
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Modern Expense Cards -->
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="expense in filteredExpenses" 
                            :key="expense.id"
                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <!-- Mobile Layout (sm and below) -->
                            <div class="block lg:hidden space-y-3">
                                <!-- Header Row -->
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                            {{ expense.name }}
                                        </h3>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <Badge 
                                                variant="outline" 
                                                class="text-xs"
                                                :style="expense.category?.color ? getCategoryBadgeStyle(expense.category.color) : {}"
                                            >
                                                {{ expense.category?.name || 'No Category' }}
                                            </Badge>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(expense.expense_date) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                            {{ formatCurrency(expense.amount) }}
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
                                        <Link :href="`/expenses/${expense.id}/edit`">
                                            <Edit class="h-3 w-3 mr-1.5" />
                                            <span class="hidden sm:inline">Edit</span>
                                        </Link>
                                    </Button>
                                    
                                    <Button 
                                        size="sm" 
                                        variant="destructive"
                                        @click="deleteExpense(expense.id)"
                                        class="h-8 px-3"
                                    >
                                        <Trash2 class="h-3 w-3 mr-1.5" />
                                        <span class="hidden sm:inline">Delete</span>
                                    </Button>
                                </div>
                            </div>

                            <!-- Desktop Layout (lg and above) -->
                            <div class="hidden lg:flex lg:items-center lg:justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                            {{ expense.name }}
                                        </h3>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <Badge 
                                            variant="outline" 
                                            :style="expense.category?.color ? getCategoryBadgeStyle(expense.category.color) : {}"
                                        >
                                            {{ expense.category?.name || 'No Category' }}
                                        </Badge>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 min-w-[100px]">
                                            {{ formatDate(expense.expense_date) }}
                                        </span>
                                        <div class="text-lg font-bold text-red-600 dark:text-red-400 min-w-[120px] text-right">
                                            {{ formatCurrency(expense.amount) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-6">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/expenses/${expense.id}/edit`">
                                            <Edit class="h-4 w-4 mr-2" />
                                            Edit
                                        </Link>
                                    </Button>
                                    
                                    <Button 
                                        size="sm" 
                                        variant="destructive"
                                        @click="deleteExpense(expense.id)"
                                    >
                                        <Trash2 class="h-4 w-4 mr-2" />
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>