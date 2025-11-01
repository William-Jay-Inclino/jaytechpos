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
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatsCard
                    title="Total Sales Today"
                    :value="dailyStats.total_sales_today"
                    icon="bar-chart-3"
                />
                <StatsCard
                    title="Total Cash Received Today"
                    :value="dailyStats.total_cash_today"
                    icon="banknote"
                />
                <StatsCard
                    title="Total Amount Receivable"
                    :value="utangStats.total_amount_receivable"
                    icon="dollar-sign"
                />
            </div>

            <!-- Charts and Tables Grid -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Best Selling Products -->
                <BestSellingProducts :products="bestSellingProducts" />
                
                <!-- Sales Chart -->
                <SalesChart :chart-data="salesChartData" />
            </div>

            <!-- Cash Flow Table -->
            <div class="grid">
                <CashFlowTable :data="cashFlowData" :current-year="currentYear" />
            </div>
        </div>
    </AppLayout>
</template>
