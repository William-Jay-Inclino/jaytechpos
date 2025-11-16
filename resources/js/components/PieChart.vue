<script setup lang="ts">
import { ref, computed } from 'vue'
import { PieChart, TrendingUp, Sparkles } from 'lucide-vue-next'
import { Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    type ChartOptions,
    type ChartData
} from 'chart.js'

// Register Chart.js components
ChartJS.register(ArcElement, Tooltip, Legend)

interface PieChartData {
    labels: string[]
    data: number[]
    colors: string[]
}

interface Props {
    chartData: PieChartData
    title: string
    loading?: boolean
    emptyMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No data available'
})

// Format currency for tooltips and labels
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}

// Calculate total for percentage calculations
const total = computed(() => {
    return props.chartData.data.reduce((sum, value) => sum + value, 0)
})

// Chart.js data configuration
const chartData = computed((): ChartData<'doughnut'> => ({
    labels: props.chartData.labels,
    datasets: [
        {
            data: props.chartData.data,
            backgroundColor: props.chartData.colors,
            borderColor: props.chartData.colors.map(color => color + '40'), // Add transparency
            borderWidth: 3,
            hoverBorderWidth: 5,
            hoverOffset: 8,
        }
    ]
}))

// Chart.js options configuration
const chartOptions = computed((): ChartOptions<'doughnut'> => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false, // We'll create a custom legend
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.95)',
            titleColor: 'rgb(243, 244, 246)',
            bodyColor: 'rgb(243, 244, 246)',
            borderColor: 'rgba(59, 130, 246, 0.3)',
            borderWidth: 1,
            cornerRadius: 12,
            padding: 16,
            displayColors: false,
            titleFont: {
                size: 14,
                weight: 'bold',
            },
            bodyFont: {
                size: 13,
            },
            callbacks: {
                title: (context) => {
                    return context[0]?.label || ''
                },
                label: (context) => {
                    const value = context.parsed
                    const percentage = total.value > 0 ? ((value / total.value) * 100).toFixed(1) : '0.0'
                    return `💰 ${formatCurrency(value || 0)} (${percentage}%)`
                }
            }
        }
    },
    cutout: '65%',
    animation: {
        duration: 1500,
        easing: 'easeInOutQuart',
    }
}))

// Custom legend data
const legendItems = computed(() => {
    return props.chartData.labels.map((label, index) => ({
        label,
        value: props.chartData.data[index],
        color: props.chartData.colors[index],
        percentage: total.value > 0 ? ((props.chartData.data[index] / total.value) * 100).toFixed(1) : '0.0'
    }))
})
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 backdrop-blur-xl shadow-2xl dark:border-gray-700/50 dark:bg-gray-800/70">
        <!-- Animated background gradients -->
        <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 via-pink-50/30 to-rose-50/50 dark:from-purple-900/20 dark:via-pink-900/10 dark:to-rose-900/20"></div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-purple-400/10 via-pink-400/5 to-transparent rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-rose-400/10 via-pink-400/5 to-transparent rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        
        <div class="relative p-6 sm:p-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl blur opacity-30 animate-pulse"></div>
                        <div class="relative flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg">
                            <PieChart class="h-6 w-6" />
                        </div>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            {{ title }}
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Chart and Legend Container -->
            <div v-if="!loading && chartData.datasets[0].data.length > 0" class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <!-- Chart -->
                <div class="relative">
                    <div class="relative h-64 w-full p-4 rounded-xl bg-gradient-to-br from-white/80 via-white/60 to-white/40 dark:from-gray-800/80 dark:via-gray-800/60 dark:to-gray-800/40 backdrop-blur-sm border border-white/30 dark:border-gray-700/30 shadow-inner">
                        <Doughnut 
                            :data="chartData" 
                            :options="chartOptions" 
                            class="h-full w-full drop-shadow-sm"
                        />
                        
                        <!-- Center text -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(total) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="space-y-4">
                    <div 
                        v-for="(item, index) in legendItems" 
                        :key="index"
                        class="relative group"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-white/50 to-white/20 dark:from-gray-700/50 dark:to-gray-700/20 rounded-lg blur opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative flex items-center justify-between p-4 rounded-lg bg-white/40 dark:bg-gray-800/40 backdrop-blur-sm border border-white/20 dark:border-gray-700/20 hover:bg-white/60 dark:hover:bg-gray-800/60 transition-all duration-300">
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-4 h-4 rounded-full shadow-sm"
                                    :style="{ backgroundColor: item.color }"
                                ></div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ item.label }}
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.value) }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.percentage }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading state -->
            <div v-else-if="loading" class="flex h-64 flex-col items-center justify-center">
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-200 to-purple-300 dark:from-purple-600 dark:to-purple-700 rounded-full blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative rounded-full bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-700 dark:to-purple-800 p-8 border-4 border-white/50 dark:border-purple-600/50 shadow-2xl">
                        <PieChart class="h-16 w-16 text-purple-500 animate-pulse" />
                    </div>
                </div>
                <div class="text-center space-y-3">
                    <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">
                        📊 Loading Chart Data...
                    </h3>
                    <div class="flex justify-center gap-2 mt-4">
                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-pink-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-2 bg-rose-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced empty state -->
            <div v-else class="flex h-64 flex-col items-center justify-center">
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded-full blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 p-8 border-4 border-white/50 dark:border-gray-600/50 shadow-2xl">
                        <PieChart class="h-16 w-16 text-gray-400 animate-bounce" style="animation-duration: 2s;" />
                    </div>
                </div>
                <div class="text-center space-y-3">
                    <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">
                        🎯 {{ emptyMessage }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-md">
                        Your pie chart will display beautiful data visualization once there's data to show!
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>