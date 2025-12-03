<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatCurrency } from '@/utils/currency';
import { ChevronLeft, ChevronRight, Edit, Trash2 } from 'lucide-vue-next';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

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

const props = withDefaults(defineProps<{
    expenses: Array<Expense>;
    categories: Array<ExpenseCategory>;
    selectedMonth: number;
    selectedYear: number;
}>(), {
    expenses: () => [],
    categories: () => [],
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
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    
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

// Get current month name
const getCurrentMonthYear = computed(() => {
    const month = monthOptions.find(m => m.value === selectedMonth.value);
    return `${month?.label} ${selectedYear.value}`;
});
</script>

<template>
    <AppLayout title="Expenses" :breadcrumbs="breadcrumbs">
        <Head title="Expenses" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Expenses</h2>
                    <p class="text-muted-foreground">
                        Track and manage your business expenses
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link href="/expenses/create">
                        <Button>
                            + Add Expense
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Date Filter (Simple Version - No Dialog) -->
            <div class="flex items-center justify-between rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold">{{ getCurrentMonthYear }}</h3>
                </div>
            </div>

            <!-- Expenses List -->
            <div class="rounded-lg border">
                <div class="divide-y">
                    <div
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex flex-col">
                                <span class="font-medium">{{ expense.name }}</span>
                                <span class="text-sm text-muted-foreground">
                                    {{ formatDate(expense.expense_date) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Badge
                                v-if="expense.category"
                                :style="getCategoryBadgeStyle(expense.category.color)"
                                variant="outline"
                            >
                                {{ expense.category.name }}
                            </Badge>
                            <span class="font-semibold">
                                {{ formatCurrency(expense.amount) }}
                            </span>
                            <div class="flex gap-2">
                                <Link :href="`/expenses/${expense.id}/edit`">
                                    <Button variant="ghost" size="icon">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="expenses.length === 0"
                        class="flex flex-col items-center justify-center py-12 text-center"
                    >
                        <p class="text-muted-foreground">No expenses found for this period</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
