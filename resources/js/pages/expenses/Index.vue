<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { formatCurrency } from '@/utils/currency';
import axios from 'axios';
import { Edit, Trash2, BarChart3, Search, Loader2 } from 'lucide-vue-next';
import MonthYearPicker from '@/components/MonthYearPicker.vue';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { showConfirmDelete } from '@/lib/swal';
import { showErrorToast, showSuccessToast } from '@/lib/toast';

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

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

const props = withDefaults(defineProps<{
    expenses: {
        data: Expense[];
        links: any[];
        meta: PaginationMeta;
    };
    categories: Array<ExpenseCategory>;
    selectedMonth: number;
    selectedYear: number;
    periodTotal: number;
    periodCount: number;
    filters: {
        search?: string;
    };
}>(), {
    categories: () => [],
    selectedMonth: new Date().getMonth() + 1,
    selectedYear: new Date().getFullYear(),
    periodTotal: 0,
    periodCount: 0,
    filters: () => ({ search: '' }),
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

// Search state (initialized from server-side filters)
const searchQuery = ref(props.filters.search || '');

// Debounced server-side search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

function applyFilters(): void {
    router.get('/expenses', {
        month: selectedMonth.value,
        year: selectedYear.value,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

// Generate badge style based on category color
const getCategoryBadgeStyle = (color: string) => {
    const hex = color.replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    
    const lightBg = `rgba(${r}, ${g}, ${b}, 0.1)`;
    const lightBorder = `rgba(${r}, ${g}, ${b}, 0.3)`;
    
    return {
        backgroundColor: lightBg,
        borderColor: lightBorder,
        color: color,
    };
};

// Handle month/year changes
function handleMonthYearChange() {
    applyFilters();
}

// Loading state
const isLoading = ref(false);
let removeStartListener: (() => void) | null = null;
let removeFinishListener: (() => void) | null = null;

onMounted(() => {
    removeStartListener = router.on('start', () => {
        isLoading.value = true;
    });
    removeFinishListener = router.on('finish', () => {
        isLoading.value = false;
    });
});

onUnmounted(() => {
    removeStartListener?.();
    removeFinishListener?.();
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
            const res = await axios.delete(`/expenses/${expenseId}`)

            const data = res?.data || {}

            if (data.success) {
                showSuccessToast(data.msg || 'Expense deleted successfully!')
                router.reload({ only: ['expenses'] })
            } else {
                // backend responded but indicated failure
                showErrorToast(data.msg || 'Failed to delete expense')
            }
        } catch (error: any) {
            // try to show a useful message from the response when available
            const message = error?.response?.data?.msg || error?.response?.data?.message || 'Failed to delete expense'
            showErrorToast(message)
        }
    }
}
</script>

<template>
    <Head title="Expenses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Action Buttons -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button v-if="expenses.meta.total > 0" as-child class="w-full sm:w-auto">
                        <Link href="/expenses/create">
                            Add Expense
                        </Link>
                    </Button>
                    <Button as-child variant="outline" class="w-full sm:w-auto">
                        <Link href="/expenses/analytics">
                            <BarChart3 class="mr-2 h-4 w-4" />
                            View Analytics
                        </Link>
                    </Button>
                </div>

                <!-- Period Selection -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <MonthYearPicker
                        v-model:model-month="selectedMonth"
                        v-model:model-year="selectedYear"
                        @update:model-month="handleMonthYearChange"
                        @update:model-year="handleMonthYearChange"
                    />
                </div>

                <!-- Search -->
                <div v-if="expenses.meta.total > 0 || searchQuery" class="relative">
                    <Search v-if="!isLoading" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Loader2 v-else class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 animate-spin" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search expenses..."
                        class="pl-10"
                    />
                </div>

                <!-- Period Summary -->
                <div v-if="periodCount > 0" class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ periodCount }} {{ periodCount === 1 ? 'expense' : 'expenses' }}</span>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span>{{ formatCurrency(periodTotal) }} total</span>
                </div>

                <!-- Expenses List -->
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    <div v-if="expenses.meta.total === 0 && !searchQuery" class="px-6 py-16 text-center">
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
                                    Add Your First Expense
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State - No Results -->
                    <div v-else-if="expenses.data.length === 0" class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <Search class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No expenses found
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Try adjusting your search terms to find what you're looking for.
                        </p>
                    </div>

                    <div v-else>

                    <!-- Mobile Cards -->
                    <div class="block lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="expense in expenses.data" 
                            :key="expense.id"
                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors space-y-3"
                        >
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
                                        <Edit class="h-3 w-3" />
                                        <span class="hidden sm:inline">Edit</span>
                                    </Link>
                                </Button>
                                
                                <Button 
                                    size="sm" 
                                    variant="destructive"
                                    @click="deleteExpense(expense.id)"
                                    class="h-8 px-3"
                                >
                                    <Trash2 class="h-3 w-3" />
                                    <span class="hidden sm:inline">Delete</span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table -->
                    <div v-if="expenses.data.length > 0" class="hidden lg:block">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-3 pl-6 pr-4 text-left text-sm font-medium text-gray-900 dark:text-white">
                                        Expense
                                    </th>
                                    <th class="py-3 px-4 w-24 text-left text-sm font-medium text-gray-900 dark:text-white">
                                        Date
                                    </th>
                                    <th class="py-3 px-4 w-36 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        Amount
                                    </th>
                                    <th class="py-3 pl-4 pr-6 w-32 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="expense in expenses.data" 
                                    :key="expense.id"
                                    class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <td class="py-4 pl-6 pr-4">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                            {{ expense.name }}
                                        </h3>
                                        <div class="mt-1">
                                            <Badge 
                                                variant="outline" 
                                                :style="expense.category?.color ? getCategoryBadgeStyle(expense.category.color) : {}"
                                                class="text-xs"
                                            >
                                                {{ expense.category?.name || 'No Category' }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 w-24">
                                        <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ formatDate(expense.expense_date) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 w-36 text-right">
                                        <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                            {{ formatCurrency(expense.amount) }}
                                        </div>
                                    </td>
                                    <td class="py-4 pl-4 pr-6 w-32">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button size="sm" variant="outline" as-child>
                                                <Link :href="`/expenses/${expense.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            
                                            <Button 
                                                size="sm" 
                                                variant="destructive"
                                                @click="deleteExpense(expense.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="expenses.data.length" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ expenses.meta.from }} to {{ expenses.meta.to }} of {{ expenses.meta.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="expenses.meta.current_page === 1"
                                @click="router.get('/expenses', { page: expenses.meta.current_page - 1, month: selectedMonth, year: selectedYear, search: searchQuery || undefined }, { preserveState: true, preserveScroll: true })"
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="expenses.meta.current_page === expenses.meta.last_page"
                                @click="router.get('/expenses', { page: expenses.meta.current_page + 1, month: selectedMonth, year: selectedYear, search: searchQuery || undefined }, { preserveState: true, preserveScroll: true })"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>