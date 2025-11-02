<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from 'axios'
import { TrendingUp, BarChart3, Sparkles } from 'lucide-vue-next'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    type ChartOptions,
    type ChartData
} from 'chart.js'

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
)

interface SalesChartData {
    labels: string[]
    data: number[]
}

interface Props {
    chartData: SalesChartData
    currentYear: number
}

const props = defineProps<Props>()

const selectedYear = ref(props.currentYear)
const loading = ref(false)
const salesChartData = ref<SalesChartData>(props.chartData)

const years = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYear - i)
})

async function updateYear() {
    loading.value = true
    
    try {
        const response = await axios.get('/api/dashboard/sales-chart', {
            params: { year: selectedYear.value }
        })
        
        salesChartData.value = response.data.data
    } catch (error) {
        console.error('Error fetching sales chart data:', error)
    } finally {
        loading.value = false
    }
}

// Format currency for tooltips and labels
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}

// Chart.js data configuration
const chartData = computed((): ChartData<'line'> => ({
    labels: salesChartData.value.labels,
    datasets: [
        {
            label: 'Sales',
            data: salesChartData.value.data,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: (context) => {
                const ctx = context.chart.ctx
                const gradient = ctx.createLinearGradient(0, 0, 0, 300)
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)')
                gradient.addColorStop(0.5, 'rgba(59, 130, 246, 0.1)')
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)')
                return gradient
            },
            pointBackgroundColor: 'rgb(59, 130, 246)',
            pointBorderColor: 'white',
            pointBorderWidth: 3,
            pointRadius: 8,
            pointHoverRadius: 12,
            pointHoverBorderWidth: 4,
            tension: 0.4,
            fill: true,
        }
    ]
}))

// Chart.js options configuration
const chartOptions = computed((): ChartOptions<'line'> => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            display: false,
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
                    const value = context.parsed.y
                    return `💰 ${formatCurrency(value || 0)}`
                }
            }
        }
    },
    scales: {
        x: {
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            border: {
                display: false,
            },
            ticks: {
                color: 'rgb(107, 114, 128)',
                font: {
                    size: 12,
                },
                maxRotation: 45,
                minRotation: 0,
                padding: 10,
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            border: {
                display: false,
            },
            ticks: {
                color: 'rgb(107, 114, 128)',
                font: {
                    size: 12,
                },
                padding: 15,
                callback: function(tickValue) {
                    return formatCurrency(Number(tickValue))
                }
            }
        }
    },
    elements: {
        line: {
            borderWidth: 4,
        },
        point: {
            hoverBackgroundColor: 'rgb(59, 130, 246)',
            hoverBorderColor: 'white',
        }
    },
    animation: {
        duration: 2000,
        easing: 'easeInOutQuart',
    }
}))
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 backdrop-blur-xl shadow-2xl dark:border-gray-700/50 dark:bg-gray-800/70">
        <!-- Animated background gradients -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/50 via-indigo-50/30 to-purple-50/50 dark:from-blue-900/20 dark:via-indigo-900/10 dark:to-purple-900/20"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-bl from-blue-400/10 via-indigo-400/5 to-transparent rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-gradient-to-tr from-purple-400/10 via-pink-400/5 to-transparent rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        
        <div class="relative p-6 sm:p-8">
            <div class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500/20 to-indigo-600/20">
                                <TrendingUp class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <h3 class="text-lg font-semibold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                                Sales Performance
                            </h3>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <label for="sales-year" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            Year:
                        </label>
                        <select
                            id="sales-year"
                            v-model="selectedYear"
                            @change="updateYear"
                            class="rounded-xl border border-gray-200/50 bg-white/50 backdrop-blur-sm px-4 py-2 text-sm font-medium text-gray-900 dark:border-gray-600/50 dark:bg-gray-700/50 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all duration-300"
                        >
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="relative" v-if="!loading && salesChartData.data.length > 0">
                <!-- Chart container with enhanced styling -->
                <div class="relative h-72 sm:h-96 w-full p-4 rounded-xl bg-gradient-to-br from-white/80 via-white/60 to-white/40 dark:from-gray-800/80 dark:via-gray-800/60 dark:to-gray-800/40 backdrop-blur-sm border border-white/30 dark:border-gray-700/30 shadow-inner">
                    <Line 
                        :data="chartData" 
                        :options="chartOptions" 
                        class="h-full w-full drop-shadow-sm"
                    />
                </div>
                
                <!-- Data summary cards -->
                <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/20 to-teal-400/20 rounded-lg blur opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative p-4 rounded-lg bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-emerald-200/30 dark:border-emerald-700/30">
                            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Peak Month</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ salesChartData.labels[salesChartData.data.indexOf(Math.max(...salesChartData.data))] || 'N/A' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-indigo-400/20 rounded-lg blur opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative p-4 rounded-lg bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-blue-200/30 dark:border-blue-700/30">
                            <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Best Sales</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(Math.max(...salesChartData.data)) }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="relative group col-span-2 sm:col-span-1">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-400/20 to-pink-400/20 rounded-lg blur opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative p-4 rounded-lg bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-purple-200/30 dark:border-purple-700/30">
                            <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Total {{ selectedYear }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(salesChartData.data.reduce((sum, value) => sum + value, 0)) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading state -->
            <div v-else-if="loading" class="flex h-72 sm:h-96 flex-col items-center justify-center">
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-200 to-blue-300 dark:from-blue-600 dark:to-blue-700 rounded-full blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative rounded-full bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-700 dark:to-blue-800 p-8 border-4 border-white/50 dark:border-blue-600/50 shadow-2xl">
                        <TrendingUp class="h-16 w-16 text-blue-500 animate-pulse" />
                    </div>
                </div>
                <div class="text-center space-y-3">
                    <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">
                        📊 Loading Sales Data...
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-md">
                        Fetching your sales performance for {{ selectedYear }}
                    </p>
                    <div class="flex justify-center gap-2 mt-4">
                        <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced empty state -->
            <div v-else class="flex h-72 sm:h-96 flex-col items-center justify-center">
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded-full blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 p-8 border-4 border-white/50 dark:border-gray-600/50 shadow-2xl">
                        <BarChart3 class="h-16 w-16 text-gray-400 animate-bounce" style="animation-duration: 2s;" />
                    </div>
                </div>
                <div class="text-center space-y-3">
                    <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">
                        🚀 Ready for Your First Sales!
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-md">
                        Your beautiful sales chart will come to life once you start making sales. Every transaction will add to your success story!
                    </p>
                    <div class="flex justify-center gap-2 mt-4">
                        <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>