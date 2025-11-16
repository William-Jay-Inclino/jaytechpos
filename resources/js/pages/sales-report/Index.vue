<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Filter, TrendingUp, DollarSign, CreditCard, Receipt, RefreshCw, Eye, X, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import SalesChart from '@/components/SalesChart.vue'
import StatsCard from '@/components/StatsCard.vue'
import SaleDetailsModal from '@/components/modals/SaleDetailsModal.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { salesReport } from '@/routes'
import { type BreadcrumbItem } from '@/types'

interface Sale {
    id: number
    invoice_number: string
    transaction_date: string
    customer_name?: string
    total_amount: number
    paid_amount: number
    payment_type: 'cash' | 'utang'
    is_utang: boolean
    cashier: string
    notes?: string
    items?: {
        id: number
        product_id: number
        product_name: string
        quantity: number
        unit_price: number
        total_amount: number
    }[]
}

interface SaleTransaction {
    id: number;
    type: 'sale';
    date: string;
    amount: number;
    formatted_amount: string;
    description: string;
    invoice_number?: string;
    payment_type?: 'cash' | 'utang';
    total_amount?: number;
    paid_amount?: number;
    notes?: string;
    previous_balance?: number;
    new_balance?: number;
    formatted_previous_balance?: string;
    formatted_new_balance?: string;
    sales_items?: {
        id: number;
        product_name: string;
        quantity: number;
        unit_price: number;
        total_price: number;
    }[];
}

interface SalesData {
    data: Sale[]
    pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    summary: {
        total_transactions: number
        total_sales: number
        total_paid: number
        cash_sales: number
        utang_sales: number
        average_sale: number
    }
}

interface ChartData {
    labels: string[]
    data: number[]
}

interface Props {
    filters: {
        period: string
        start_date?: string
        end_date?: string
        payment_type: string
        page?: number
    }
    sales: SalesData
    chartData: ChartData
}

const props = defineProps<Props>()

const loading = ref(false)
const salesData = ref<SalesData>(props.sales)
const chartData = ref<ChartData>(props.chartData)

// Date filter form data (affects chart and stats)
const filters = ref({
    period: props.filters.period || 'month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    payment_type: 'all', // Remove payment type from main filters
})

// Separate payment filter for table only
const tablePaymentFilter = ref(props.filters.payment_type || 'all')

// Pagination state
const currentPage = ref(props.filters.page || 1)
const perPage = ref(20)

// Sale details modal state
const showSaleDetails = ref(false)
const selectedSale = ref<SaleTransaction | null>(null)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sales Report', href: salesReport().url },
]

// Computed values
const isCustomPeriod = computed(() => filters.value.period === 'custom')

// Since we're now filtering on the backend, we don't need client-side filtering
const filteredSalesData = computed(() => {
    return salesData.value.data
})

// Format currency
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}

// Format date
const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// Apply filters and fetch new data
const applyFilters = async (page: number = 1) => {
    if (isCustomPeriod.value && (!filters.value.start_date || !filters.value.end_date)) {
        return // Don't apply if custom period is selected but dates are not provided
    }

    loading.value = true
    
    try {
        // Create params without payment_type as it's handled client-side for table
        const params = new URLSearchParams({
            period: filters.value.period,
            start_date: filters.value.start_date,
            end_date: filters.value.end_date,
            payment_type: tablePaymentFilter.value, // Use table filter for backend pagination
            page: page.toString(),
            per_page: perPage.value.toString(),
        })
        
        // Fetch data in parallel
        const [salesResponse, chartResponse] = await Promise.all([
            axios.get(`/api/sales-report/data?${params}`),
            axios.get(`/api/sales-report/chart?${params}`),
        ])

        salesData.value = salesResponse.data.data
        chartData.value = chartResponse.data.data
        currentPage.value = page

        // Update URL without page reload
        router.visit('/sales-report', {
            data: {
                period: filters.value.period,
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
                payment_type: tablePaymentFilter.value,
                page: page,
            },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    } catch (error) {
        console.error('Error fetching sales report data:', error)
    } finally {
        loading.value = false
    }
}

// Reset filters to this month
const resetFilters = () => {
    filters.value = {
        period: 'month',
        start_date: '',
        end_date: '',
        payment_type: 'all',
    }
    tablePaymentFilter.value = 'all'
    currentPage.value = 1
    applyFilters(1)
}

// Apply custom date range
const applyCustomRange = () => {
    if (filters.value.start_date && filters.value.end_date) {
        currentPage.value = 1
        applyFilters(1)
    }
}

// Pagination functions
const goToPage = (page: number) => {
    if (page >= 1 && page <= salesData.value.pagination.last_page) {
        applyFilters(page)
    }
}

const goToPreviousPage = () => {
    if (currentPage.value > 1) {
        goToPage(currentPage.value - 1)
    }
}

const goToNextPage = () => {
    if (currentPage.value < salesData.value.pagination.last_page) {
        goToPage(currentPage.value + 1)
    }
}

// View sale details
const viewSaleDetails = async (sale: Sale) => {
    loading.value = true
    try {
        // Get CSRF token from the meta tag
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/api/sales/${sale.id}`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        selectedSale.value = data.sale;
        showSaleDetails.value = true;
    } catch (error) {
        console.error('Error fetching sale details:', error);
    } finally {
        loading.value = false
    }
}



// Watch for period changes to clear custom dates when needed
watch(() => filters.value.period, (newPeriod) => {
    if (newPeriod !== 'custom') {
        filters.value.start_date = ''
        filters.value.end_date = ''
        currentPage.value = 1
        applyFilters(1)
    }
})

// Watch for payment type changes to reset pagination and apply filter
watch(tablePaymentFilter, () => {
    currentPage.value = 1
    applyFilters(1)
})

// Generate visible page numbers for pagination
const getVisiblePages = (): (number | string)[] => {
    const totalPages = salesData.value.pagination.last_page
    const current = currentPage.value
    const pages: (number | string)[] = []

    if (totalPages <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= totalPages; i++) {
            pages.push(i)
        }
    } else {
        // Always show first page
        pages.push(1)

        if (current > 3) {
            pages.push('...')
        }

        // Show pages around current page
        const start = Math.max(2, current - 1)
        const end = Math.min(totalPages - 1, current + 1)

        for (let i = start; i <= end; i++) {
            pages.push(i)
        }

        if (current < totalPages - 2) {
            pages.push('...')
        }

        // Always show last page if it's not already included
        if (totalPages > 1) {
            pages.push(totalPages)
        }
    }

    return pages
}
</script>

<template>
    <Head title="Sales Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950">
            <!-- Hero Section -->
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-indigo-600/5 dark:from-blue-400/5 dark:to-indigo-400/5"></div>
                <div class="absolute top-0 right-0 -mt-4 -mr-16 w-96 h-96 bg-gradient-to-br from-blue-400/10 to-indigo-500/10 rounded-full blur-3xl"></div>
                
                <div class="relative px-4 py-8 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <!-- Header -->
                        <!-- <div class="text-center mb-8">
                            <div class="flex items-center justify-center gap-3 mb-4">
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl blur opacity-30 animate-pulse"></div>
                                    <div class="relative flex h-16 w-16 items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg">
                                        <TrendingUp class="h-8 w-8" />
                                    </div>
                                </div>
                                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                                    Sales Report
                                </h1>
                            </div>
                        </div> -->

                        <!-- Date Filters -->
                        <Card class="mb-8 border-white/20 bg-white/70 backdrop-blur-xl shadow-xl dark:border-gray-700/50 dark:bg-gray-800/70">
                            <CardHeader>
                                <div class="flex items-center gap-2">
                                    <Filter class="h-5 w-5 text-blue-500" />
                                    <CardTitle>Date Filters</CardTitle>
                                </div>
                                <CardDescription>
                                    Filter your sales data by time period
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <!-- Period Toggle Buttons -->
                                <div class="space-y-4">
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">Time Period</Label>
                                        <div class="flex flex-wrap gap-2">
                                            <Button 
                                                @click="filters.period = 'today'"
                                                :variant="filters.period === 'today' ? 'default' : 'outline'"
                                                size="sm"
                                                class="transition-all duration-200"
                                            >
                                                Today
                                            </Button>
                                            <Button 
                                                @click="filters.period = 'week'"
                                                :variant="filters.period === 'week' ? 'default' : 'outline'"
                                                size="sm"
                                                class="transition-all duration-200"
                                            >
                                                This Week
                                            </Button>
                                            <Button 
                                                @click="filters.period = 'month'"
                                                :variant="filters.period === 'month' ? 'default' : 'outline'"
                                                size="sm"
                                                class="transition-all duration-200"
                                            >
                                                This Month
                                            </Button>
                                            <Button 
                                                @click="filters.period = 'year'"
                                                :variant="filters.period === 'year' ? 'default' : 'outline'"
                                                size="sm"
                                                class="transition-all duration-200"
                                            >
                                                This Year
                                            </Button>
                                            <Button 
                                                @click="filters.period = 'custom'"
                                                :variant="filters.period === 'custom' ? 'default' : 'outline'"
                                                size="sm"
                                                class="transition-all duration-200"
                                            >
                                                Custom Range
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Custom Date Range -->
                                    <div v-show="isCustomPeriod" class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <Label for="start_date">Start Date</Label>
                                                <Input
                                                    id="start_date"
                                                    v-model="filters.start_date"
                                                    type="date"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="end_date">End Date</Label>
                                                <Input
                                                    id="end_date"
                                                    v-model="filters.end_date"
                                                    type="date"
                                                />
                                            </div>
                                            <div class="flex items-end">
                                                <Button 
                                                    @click="applyCustomRange"
                                                    :disabled="!filters.start_date || !filters.end_date"
                                                    size="sm"
                                                    class="w-full"
                                                >
                                                    Apply Range
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reset Button -->
                                    <div class="flex justify-end">
                                        <Button @click="resetFilters" variant="outline" size="sm">
                                            <RefreshCw class="h-4 w-4 mr-1" />
                                            Reset
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Summary Stats -->
                        <div class="grid gap-6 grid-cols-1 mb-8">
                            <StatsCard
                                title="Total Sales"
                                :value="salesData.summary.total_sales"
                                icon="dollar-sign"
                                class="transform hover:scale-105 transition-all duration-300"
                            />
                        </div>

                        <!-- Charts Section -->
                        <div class="mb-8">
                            <!-- Sales Chart -->
                            <div class="relative">
                                <SalesChart 
                                    :chart-data="chartData" 
                                    :current-year="new Date().getFullYear()"
                                    :disable-year-selector="true"
                                    :loading="loading"
                                    class="transform hover:shadow-2xl transition-all duration-500" 
                                />
                            </div>
                        </div>

                        <!-- Sales Table -->
                        <Card class="border-white/20 bg-white/70 backdrop-blur-xl shadow-xl dark:border-gray-700/50 dark:bg-gray-800/70">
                            <CardHeader>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-2">
                                        <Receipt class="h-5 w-5 text-blue-500" />
                                        <CardTitle>Sales Transactions</CardTitle>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <!-- Payment Type Filter -->
                                        <div class="flex items-center gap-2">
                                            <Label class="text-sm font-medium whitespace-nowrap">Payment Type:</Label>
                                            <Select v-model="tablePaymentFilter">
                                                <SelectTrigger class="w-36">
                                                    <SelectValue placeholder="All" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">All Types</SelectItem>
                                                    <SelectItem value="cash">Cash Only</SelectItem>
                                                    <SelectItem value="utang">Utang Only</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <th class="text-left py-3 px-2 font-medium text-gray-700 dark:text-gray-300">Invoice</th>
                                                <th class="text-left py-3 px-2 font-medium text-gray-700 dark:text-gray-300">Date</th>
                                                <th class="text-left py-3 px-2 font-medium text-gray-700 dark:text-gray-300">Customer</th>
                                                <th class="text-right py-3 px-2 font-medium text-gray-700 dark:text-gray-300">Amount</th>
                                                <th class="text-center py-3 px-2 font-medium text-gray-700 dark:text-gray-300">Type</th>
                                                <th class="text-center py-3 px-2 font-medium text-gray-700 dark:text-gray-300"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr 
                                                v-for="sale in filteredSalesData" 
                                                :key="sale.id"
                                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200"
                                            >
                                                <td class="py-3 px-2">
                                                    <div class="font-medium text-gray-900 dark:text-white">
                                                        {{ sale.invoice_number }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-2">
                                                    <div class="text-gray-600 dark:text-gray-400">
                                                        {{ formatDate(sale.transaction_date) }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-2">
                                                    <div class="text-gray-600 dark:text-gray-400">
                                                        {{ sale.customer_name || 'Walk-in Customer' }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-2 text-right">
                                                    <div class="font-semibold text-gray-900 dark:text-white">
                                                        {{ formatCurrency(sale.total_amount) }}
                                                    </div>
                                                    <div v-if="sale.is_utang" class="text-xs text-gray-500 dark:text-gray-400">
                                                        Paid: {{ formatCurrency(sale.paid_amount) }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-2 text-center">
                                                    <Badge 
                                                        :variant="sale.payment_type === 'cash' ? 'default' : 'secondary'"
                                                        class="capitalize"
                                                    >
                                                        <CreditCard v-if="sale.payment_type === 'utang'" class="h-3 w-3 mr-1" />
                                                        <DollarSign v-else class="h-3 w-3 mr-1" />
                                                        {{ sale.payment_type }}
                                                    </Badge>
                                                </td>
                                                <td class="py-3 px-2 text-center">
                                                    <Button 
                                                        @click="viewSaleDetails(sale)"
                                                        variant="ghost" 
                                                        size="sm"
                                                        class="h-8 w-8 p-0"
                                                    >
                                                        <Eye class="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Empty State -->
                                    <div v-if="salesData.data.length === 0" class="text-center py-12">
                                        <div class="relative mb-8">
                                            <div class="absolute inset-0 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded-full blur-xl opacity-50"></div>
                                            <div class="relative rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 p-8 border-4 border-white/50 dark:border-gray-600/50 shadow-2xl mx-auto w-24 h-24 flex items-center justify-center">
                                                <Receipt class="h-12 w-12 text-gray-400" />
                                            </div>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-2">
                                            No Sales Found
                                        </h3>
                                        <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                            No sales transactions found for the selected filters.
                                        </p>
                                    </div>
                                </div>

                                <!-- Pagination Controls -->
                                <div v-if="salesData.pagination.last_page > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Showing {{ ((salesData.pagination.current_page - 1) * salesData.pagination.per_page) + 1 }} 
                                        to {{ Math.min(salesData.pagination.current_page * salesData.pagination.per_page, salesData.pagination.total) }} 
                                        of {{ salesData.pagination.total }} results
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <!-- Previous Button -->
                                        <Button 
                                            @click="goToPreviousPage"
                                            :disabled="currentPage <= 1 || loading"
                                            variant="outline" 
                                            size="sm"
                                            class="flex items-center gap-1"
                                        >
                                            <ChevronLeft class="h-4 w-4" />
                                            Previous
                                        </Button>

                                        <!-- Page Numbers -->
                                        <div class="flex items-center gap-1">
                                            <template v-for="page in getVisiblePages()" :key="page">
                                                <Button
                                                    v-if="page === '...'"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled
                                                    class="w-10 h-8 p-0"
                                                >
                                                    ...
                                                </Button>
                                                <Button
                                                    v-else
                                                    @click="goToPage(page as number)"
                                                    :variant="currentPage === page ? 'default' : 'outline'"
                                                    :disabled="loading"
                                                    size="sm"
                                                    class="w-10 h-8 p-0"
                                                >
                                                    {{ page }}
                                                </Button>
                                            </template>
                                        </div>

                                        <!-- Next Button -->
                                        <Button 
                                            @click="goToNextPage"
                                            :disabled="currentPage >= salesData.pagination.last_page || loading"
                                            variant="outline" 
                                            size="sm"
                                            class="flex items-center gap-1"
                                        >
                                            Next
                                            <ChevronRight class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Sale Details Modal -->
            <SaleDetailsModal 
                :open="showSaleDetails"
                :transaction="selectedSale"
                @update:open="showSaleDetails = $event"
            />

            <!-- Loading Overlay -->
            <div v-if="loading" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
                    <div class="flex items-center gap-4">
                        <RefreshCw class="h-8 w-8 text-blue-500 animate-spin" />
                        <span class="text-lg font-medium text-gray-900 dark:text-white">Loading sales data...</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>