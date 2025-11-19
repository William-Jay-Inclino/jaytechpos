<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ExpensePieChart from '@/components/ExpensePieChart.vue';
import ExpenseCategoryDetailsModal from '@/components/modals/ExpenseCategoryDetailsModal.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, withDefaults, watch } from 'vue';
import { ChevronLeft, ChevronRight, Info } from 'lucide-vue-next';
import axios from 'axios';

// UI Components
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

interface ChartDataItem {
    id?: number;
    label: string;
    amount: number;
    count: number;
    color: string;
}

interface Expense {
    id: number;
    name: string;
    amount: number;
    expense_date: string;
}

const props = withDefaults(defineProps<{
    chartData: Array<ChartDataItem>;
    selectedYear: number;
    monthlyExpenses?: Array<{ month: string; amount: number }>;
}>(), {
    chartData: () => [],
    selectedYear: new Date().getFullYear(),
    monthlyExpenses: () => [],
});
const monthOrder = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const sortedMonthlyExpenses = computed(() => {
    // Ensure all months are present (Jan-Dec). If a month is missing from
    // props.monthlyExpenses, show it with amount 0 so the table always has
    // twelve rows.
    const map = new Map<string, number>();

    if (Array.isArray(props.monthlyExpenses)) {
        for (const item of props.monthlyExpenses) {
            if (item && typeof item.month === 'string') {
                map.set(item.month, Number(item.amount) || 0);
            }
        }
    }

    return monthOrder.map((m) => ({
        month: m,
        amount: map.get(m) ?? 0,
    }));
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses',
        href: '/expenses',
    },
    {
        title: 'Analytics',
        href: '/expenses/analytics',
    },
];

// Date filter state
const selectedYear = ref(props.selectedYear);
const showDateDialog = ref(false);

// Category details modal
const showCategoryModal = ref(false);
const selectedCategory = ref<ChartDataItem | null>(null);
const categoryExpenses = ref<Expense[]>([]);
const loadingExpenses = ref(false);

// Computed properties
const totalAmount = computed(() => {
    if (!Array.isArray(props.chartData)) return 0;
    return props.chartData.reduce((sum, item) => sum + item.amount, 0);
});

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
};

// Generate year options (current year ± 5 years)
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 11 }, (_, i) => currentYear - 5 + i);
// Current month name (e.g. "November"). Used to highlight the row when
// the user is viewing the current year.
const currentMonthName = monthOrder[new Date().getMonth()];

// Update expenses when year changes
function updateAnalytics() {
    router.get('/expenses/analytics', {
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Navigate to previous year
function goToPreviousYear() {
    selectedYear.value--;
}

// Navigate to next year
function goToNextYear() {
    selectedYear.value++;
}

// Handle category click
async function handleCategoryClick(category: ChartDataItem) {
    if (!category.id) return;
    
    selectedCategory.value = category;
    loadingExpenses.value = true;
    showCategoryModal.value = true;
    
    try {
        const response = await axios.get(`/expenses/category/${category.id}`, {
            params: {
                year: selectedYear.value,
            },
        });
        
        categoryExpenses.value = response.data.expenses;
    } catch (error) {
        console.error('Error loading category expenses:', error);
        categoryExpenses.value = [];
    } finally {
        loadingExpenses.value = false;
    }
}

// Watch for year changes
watch(selectedYear, () => {
    updateAnalytics();
});
</script>

<template>
    <Head title="Expense Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Year Selection -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="goToPreviousYear"
                                class="h-8 w-8 p-0"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            
                            <Dialog v-model:open="showDateDialog">
                                <DialogTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        class="min-w-[100px] font-medium text-gray-900 hover:text-gray-700 dark:text-white dark:hover:text-gray-300"
                                    >
                                        {{ selectedYear }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Select Year</DialogTitle>
                                        <DialogDescription>
                                            Choose the year to view expense analytics for.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="grid gap-4 py-4">
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
                                @click="goToNextYear"
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
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Expense Breakdown by Category
                            </h2>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <Info class="h-3.5 w-3.5" />
                                Click on any category to view detailed expenses
                            </p>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total: {{ formatCurrency(totalAmount) }}
                        </div>
                    </div>
                    
                    <div v-if="!Array.isArray(chartData) || chartData.length === 0" class="py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl">📊</span>
                        </div>
                        <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">
                            No data available
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            There are no expenses recorded for {{ selectedYear }}.
                        </p>
                    </div>
                    
                    <ExpensePieChart v-else :data="chartData" @category-click="handleCategoryClick" />
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Monthly Expenses
                            </h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Total expenses for each month in {{ selectedYear }}
                            </p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900">Month</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-if="sortedMonthlyExpenses.length === 0">
                                    <td colspan="2" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 text-base">
                                        No monthly expense data available.
                                    </td>
                                </tr>
                                <tr
                                    v-for="row in sortedMonthlyExpenses"
                                    :key="row.month"
                                    :class="[
                                        (selectedYear === currentYear && row.month === currentMonthName) ? 'border-l-4 border-yellow-300 dark:border-yellow-600' : '',
                                        'hover:bg-gray-50 dark:hover:bg-gray-900',
                                        'transition-colors'
                                    ]"
                                >
                                    <td
                                        :class="[
                                            (selectedYear === currentYear && row.month === currentMonthName) ? 'font-semibold' : '',
                                            'px-6 py-4 whitespace-nowrap text-base text-gray-900 dark:text-white flex items-center gap-2'
                                        ]"
                                    >
                                        <span v-if="selectedYear === currentYear && row.month === currentMonthName" class="inline-block w-2 h-2 rounded-full bg-yellow-400 dark:bg-yellow-500 mr-2" aria-hidden="true"></span>
                                        <span>{{ row.month }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-base font-semibold text-primary dark:text-primary">
                                        {{ formatCurrency(row.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>


        <ExpenseCategoryDetailsModal
            v-model:open="showCategoryModal"
            :category-name="selectedCategory?.label || ''"
            :category-color="selectedCategory?.color || '#6B7280'"
            :expenses="categoryExpenses"
            :year="selectedYear"
        />
    </AppLayout>
</template>
