<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import StatsCard from '@/components/StatsCard.vue'
import BestSellingProducts from '@/components/BestSellingProducts.vue'
import CashFlowTable from '@/components/CashFlowTable.vue'
import SalesChart from '@/components/SalesChart.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'

interface DashboardProps {
    dailyStats: {
        total_sales_today: number
        total_cash_today: number
        utang_payments_today: number
    }
    utangStats: {
        total_amount_receivable: number
    }
    bestSellingProducts: {
        today: Array<{ id: number; product_name: string; total_sold: number }>
        week: Array<{ id: number; product_name: string; total_sold: number }>
        month: Array<{ id: number; product_name: string; total_sold: number }>
        year: Array<{ id: number; product_name: string; total_sold: number }>
    }
    cashFlowData: Array<{
        month: number
        income: number
        expense: number
        cash_flow: number
    }>
    salesChartData: {
        labels: string[]
        data: number[]
    }
    currentYear: number
}

const props = defineProps<DashboardProps>()

console.log('props', props);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
]
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Modern Gradient Background -->
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950">
            <!-- Hero Section with Stats -->
            <div class="relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-indigo-600/5 dark:from-blue-400/5 dark:to-indigo-400/5"></div>
                <div class="absolute top-0 right-0 -mt-4 -mr-16 w-96 h-96 bg-gradient-to-br from-blue-400/10 to-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-8 -ml-16 w-80 h-80 bg-gradient-to-tr from-indigo-400/10 to-purple-500/10 rounded-full blur-3xl"></div>
                
                <div class="relative px-4 py-8 sm:px-6 lg:px-8">
                    <!-- Welcome Section -->
                    <div class="mb-8 text-center">
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent mb-3">
                            Welcome to Your Dashboard
                        </h1>
                    </div>

                    <!-- Statistics Cards Grid -->
                    <div class="max-w-7xl mx-auto">
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-12">
                            <StatsCard
                                title="Congratulations! Kumikita ka ngayong araw ng"
                                :value="dailyStats.total_sales_today"
                                icon="bar-chart-3"
                                class="transform hover:scale-105 transition-all duration-300"
                            />
                            <StatsCard
                                title="Ayos! May nabayarang utang ngayong araw na"
                                :value="dailyStats.utang_payments_today"
                                icon="banknote"
                                class="transform hover:scale-105 transition-all duration-300"
                            />
                            <StatsCard
                                title="May makokolekta ka pang utang na"
                                :value="utangStats.total_amount_receivable"
                                icon="dollar-sign"
                                class="sm:col-span-2 lg:col-span-1 transform hover:scale-105 transition-all duration-300"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="relative px-4 pb-12 sm:px-6 lg:px-8">
                <div class="max-w-7xl mx-auto space-y-8">
                    <!-- Sales Chart Section -->
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/5 to-teal-500/5 rounded-3xl blur-xl"></div>
                        <div class="relative">
                            <SalesChart :chart-data="salesChartData" class="transform hover:shadow-2xl transition-all duration-500" />
                        </div>
                    </div>

                    <!-- Analytics Grid -->
                    <div class="grid gap-8 lg:grid-cols-2">
                        <!-- Best Selling Products -->
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative">
                                <BestSellingProducts 
                                    :products="bestSellingProducts" 
                                    class="transform hover:shadow-2xl transition-all duration-500"
                                />
                            </div>
                        </div>
                        
                        <!-- Cash Flow Table -->
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative">
                                <CashFlowTable 
                                    :data="cashFlowData" 
                                    :current-year="currentYear" 
                                    class="transform hover:shadow-2xl transition-all duration-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floating Action Button -->
            <div class="fixed bottom-8 right-8 z-10">
                <button class="w-14 h-14 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
            </div>
        </div>
    </AppLayout>
</template>
