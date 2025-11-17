<script setup lang="ts">
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { X } from 'lucide-vue-next';

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
}

const props = defineProps<Props>();

console.log('expenses', props.expenses);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
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
    
    // Sort by month order
    const monthOrder = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    return Array.from(grouped.entries())
        .sort((a, b) => monthOrder.indexOf(a[0]) - monthOrder.indexOf(b[0]))
        .map(([month, expenses]) => {
            // Helper to parse and sanitize amount
            const parseAmount = (val: any) => {
                if (typeof val === 'number') return val;
                if (typeof val === 'string') {
                    // Remove all except digits and decimal point, then parse
                    const cleaned = val.replace(/[^\d.]/g, '');
                    // If multiple decimals, keep only the first
                    const parts = cleaned.split('.');
                    if (parts.length > 2) {
                        return parseFloat(parts[0] + '.' + parts.slice(1).join(''));
                    }
                    return parseFloat(cleaned);
                }
                return 0;
            };
            return {
                month,
                expenses: expenses.sort((a, b) => 
                    new Date(b.expense_date).getTime() - new Date(a.expense_date).getTime()
                ),
                total: expenses.reduce((sum, exp) => sum + parseAmount(exp.amount), 0),
            };
        });
});

// Total amount (parse and sanitize like in expensesByMonth)
const parseAmount = (val: any) => {
    if (typeof val === 'number') return val;
    if (typeof val === 'string') {
        // Remove all except digits and decimal point, then parse
        const cleaned = val.replace(/[^\d.]/g, '');
        // If multiple decimals, keep only the first
        const parts = cleaned.split('.');
        if (parts.length > 2) {
            return parseFloat(parts[0] + '.' + parts.slice(1).join(''));
        }
        return parseFloat(cleaned);
    }
    return 0;
};

const totalAmount = computed(() => {
    return props.expenses.reduce((sum, expense) => sum + parseAmount(expense.amount), 0);
});

// Generate badge style based on category color
const getCategoryBadgeStyle = () => {
    const hex = props.categoryColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    
    const lightBg = `rgba(${r}, ${g}, ${b}, 0.1)`;
    const lightBorder = `rgba(${r}, ${g}, ${b}, 0.3)`;
    
    return {
        backgroundColor: lightBg,
        borderColor: lightBorder,
        color: props.categoryColor,
    };
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-4xl w-[calc(100vw-2rem)] max-h-[90vh] sm:max-h-[85vh] p-0 overflow-hidden flex flex-col gap-0">
            <!-- Header -->
            <DialogHeader class="px-4 sm:px-6 pt-4 sm:pt-6 pb-3 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                    <div class="flex-1 min-w-0">
                        <DialogTitle class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {{ categoryName }}
                        </DialogTitle>
                        <DialogDescription class="mt-1 sm:mt-1.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                            All expenses in this category for {{ year }}
                        </DialogDescription>
                    </div>
                    <Badge 
                        variant="outline" 
                        :style="getCategoryBadgeStyle()"
                        class="self-start sm:ml-auto flex-shrink-0 text-xs sm:text-sm font-semibold px-2.5 py-1 sm:px-3 sm:py-1.5 whitespace-nowrap"
                    >
                        {{ expenses.length }} {{ expenses.length === 1 ? 'expense' : 'expenses' }}
                    </Badge>
                </div>
            </DialogHeader>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-3 sm:py-4">
                <!-- Empty State -->
                <div v-if="expenses.length === 0" class="flex items-center justify-center py-12 sm:py-16">
                    <div class="text-center px-4">
                        <div class="mx-auto flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl sm:text-4xl">📋</span>
                        </div>
                        <h3 class="mt-4 sm:mt-6 text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
                            No expenses found
                        </h3>
                        <p class="mt-1.5 sm:mt-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            There are no expenses in this category for {{ year }}.
                        </p>
                    </div>
                </div>

                <!-- Expenses List -->
                <div v-else class="space-y-6 sm:space-y-8 pb-2">
                    <div 
                        v-for="monthData in expensesByMonth" 
                        :key="monthData.month"
                        class="space-y-2 sm:space-y-3"
                    >
                        <!-- Month Header -->
                        <div class="sticky top-0 -mx-4 sm:-mx-6 px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm border-y border-gray-200 dark:border-gray-700 z-10 shadow-sm">
                            <div class="flex items-baseline justify-between gap-2">
                                <h3 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                    {{ monthData.month }}
                                </h3>
                                <span class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 tabular-nums">
                                    {{ formatCurrency(monthData.total) }}
                                </span>
                            </div>
                            <!-- Expense count for month (helpful for large lists) -->
                            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ monthData.expenses.length }} {{ monthData.expenses.length === 1 ? 'expense' : 'expenses' }}
                            </p>
                        </div>

                        <!-- Month Expenses -->
                        <div class="space-y-1.5 sm:space-y-2">
                            <div 
                                v-for="expense in monthData.expenses" 
                                :key="expense.id"
                                class="group relative flex items-center gap-2 sm:gap-4 p-3 sm:p-4 rounded-lg border border-gray-200 bg-white hover:shadow-sm hover:border-gray-300 transition-all duration-200 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:bg-gray-700/50 dark:hover:border-gray-600"
                            >
                                <!-- Expense Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white leading-snug break-words">
                                        {{ expense.name }}
                                    </p>
                                    <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5 sm:mt-1 flex items-center gap-1 sm:gap-1.5">
                                        <span class="text-xs sm:text-sm">📅</span>
                                        <span class="whitespace-nowrap">{{ formatDate(expense.expense_date) }}</span>
                                    </p>
                                </div>
                                
                                <!-- Amount -->
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-sm sm:text-base font-bold text-red-600 dark:text-red-400 tabular-nums whitespace-nowrap">
                                        {{ formatCurrency(expense.amount) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer with Total -->
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                        Total for {{ year }}
                    </span>
                    <span class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400 tabular-nums">
                        {{ formatCurrency(totalAmount) }}
                    </span>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
