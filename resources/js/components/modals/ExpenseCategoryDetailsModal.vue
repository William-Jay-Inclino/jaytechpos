<script setup lang="ts">
import { computed } from 'vue';
import { X, Loader2 } from 'lucide-vue-next';

interface Expense {
    id: number;
    name: string;
    amount: number;
    expense_date: string;
}

interface Props {
    open: boolean;
    categoryName: string;
    categoryColor: string;
    expenses: Expense[];
    year: number;
    loading?: boolean;
    hasMorePages?: boolean;
    loadingMore?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    hasMorePages: false,
    loadingMore: false,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'load-more'): void;
}>();

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
        day: 'numeric',
        year: 'numeric',
    });
};

// Get month name from date
const getMonthName = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
    });
};

// Helper to parse and sanitize amount
const parseAmount = (val: any): number => {
    if (typeof val === 'number') return val;
    if (typeof val === 'string') {
        const cleaned = val.replace(/[^\d.]/g, '');
        const parts = cleaned.split('.');
        if (parts.length > 2) {
            return parseFloat(parts[0] + '.' + parts.slice(1).join(''));
        }
        return parseFloat(cleaned);
    }
    return 0;
};

// Group expenses by month
const expensesByMonth = computed(() => {
    const grouped = new Map<string, Expense[]>();
    
    props.expenses.forEach(expense => {
        const month = getMonthName(expense.expense_date);
        if (!grouped.has(month)) {
            grouped.set(month, []);
        }
        grouped.get(month)!.push(expense);
    });
    
    const monthOrder = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    return Array.from(grouped.entries())
        .sort((a, b) => monthOrder.indexOf(a[0]) - monthOrder.indexOf(b[0]))
        .map(([month, expenses]) => ({
            month,
            expenses: expenses.sort((a, b) => 
                new Date(b.expense_date).getTime() - new Date(a.expense_date).getTime()
            ),
            total: expenses.reduce((sum, exp) => sum + parseAmount(exp.amount), 0),
        }));
});

const totalAmount = computed(() => {
    return props.expenses.reduce((sum, expense) => sum + parseAmount(expense.amount), 0);
});
</script>

<template>
    <!-- Modal Overlay -->
    <Transition
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
            @click="emit('update:open', false)"
        ></div>
    </Transition>

    <!-- Modal Content -->
    <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="relative w-full max-w-3xl max-h-[85vh] bg-white dark:bg-gray-800 rounded-lg shadow-xl flex flex-col overflow-hidden"
                @click.stop
            >
                <!-- Header -->
                <div class="px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <div 
                                    class="h-3 w-3 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: categoryColor }"
                                ></div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ categoryName }}
                                </h2>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <button
                                @click="emit('update:open', false)"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <!-- Loading State -->
                    <div v-if="loading" class="flex items-center justify-center py-16">
                        <Loader2 class="h-8 w-8 animate-spin text-gray-400" />
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="expenses.length === 0" class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-4xl">📋</span>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No expenses found
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            There are no expenses in this category for {{ year }}.
                        </p>
                    </div>
                    </div>

                    <!-- Expenses List -->
                    <div v-else class="space-y-6">
                    <div 
                        v-for="monthData in expensesByMonth" 
                        :key="monthData.month"
                    >
                        <!-- Month Section -->
                        <div class="space-y-3">
                            <!-- Month Header -->
                            <div class="flex items-center justify-between py-2 border-b-2 border-gray-300 dark:border-gray-600">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">
                                    {{ monthData.month }}
                                </h3>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ monthData.expenses.length }} {{ monthData.expenses.length === 1 ? 'item' : 'items' }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(monthData.total) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Expenses Cards -->
                            <div class="space-y-2">
                                <div 
                                    v-for="expense in monthData.expenses" 
                                    :key="expense.id"
                                    class="group flex items-center justify-between gap-4 p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-150 dark:bg-gray-800/50 dark:hover:bg-gray-700/50"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ expense.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ formatDate(expense.expense_date) }}
                                        </p>
                                    </div>
                                    
                                    <div class="text-base font-bold text-gray-900 dark:text-white tabular-nums flex-shrink-0">
                                        {{ formatCurrency(expense.amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Load More -->
                    <div v-if="hasMorePages" class="flex items-center justify-center py-2">
                        <button
                            @click="emit('load-more')"
                            :disabled="loadingMore"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors disabled:opacity-50 flex items-center gap-2"
                        >
                            <Loader2 v-if="loadingMore" class="h-4 w-4 animate-spin" />
                            {{ loadingMore ? 'Loading...' : 'Load more' }}
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
