<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from 'axios'
import { BarChart3 } from 'lucide-vue-next'

interface CashFlowData {
    month: number
    income: number
    expense: number
    cash_flow: number
}

interface Props {
    data: CashFlowData[]
    currentYear: number
}

const props = defineProps<Props>()

const selectedYear = ref(props.currentYear)
const loading = ref(false)
const cashFlowData = ref<CashFlowData[]>(props.data)

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

const years = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYear - i)
})

const totalIncome = computed(() => 
    cashFlowData.value.reduce((sum, item) => sum + item.income, 0)
)

const totalExpense = computed(() => 
    cashFlowData.value.reduce((sum, item) => sum + item.expense, 0)
)

const totalCashFlow = computed(() => 
    cashFlowData.value.reduce((sum, item) => sum + item.cash_flow, 0)
)

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value)
}

async function updateYear() {
    loading.value = true
    
    try {
        const response = await axios.get('/api/dashboard/cash-flow', {
            params: { year: selectedYear.value }
        })
        
        cashFlowData.value = response.data.data
    } catch (error) {
        console.error('Error fetching cash flow data:', error)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-white/20 bg-white/70 backdrop-blur-xl shadow-xl dark:border-gray-700/50 dark:bg-gray-800/70">
        <div class="border-b border-gray-200/50 dark:border-gray-700/50 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20">
                            <BarChart3 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-lg font-semibold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            Cash Flow Analysis
                        </h3>
                    </div>
                </div>
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <label for="year" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Year:
                    </label>
                    <select
                        id="year"
                        v-model="selectedYear"
                        @change="updateYear"
                        class="rounded-xl border border-gray-200/50 bg-white/50 backdrop-blur-sm px-4 py-2 text-sm font-medium text-gray-900 dark:border-gray-600/50 dark:bg-gray-700/50 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all duration-300"
                    >
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto px-1 sm:px-0">
            <table class="w-full min-w-[320px] sm:min-w-[480px]">
                <thead class="bg-gray-50/50 dark:bg-gray-700/30 backdrop-blur-sm border-b border-gray-200/50 dark:border-gray-600/50">
                    <tr class="text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        <th class="p-2 sm:p-4 text-left">Month</th>
                        <th class="p-2 sm:p-4 text-right">Income</th>
                        <th class="p-2 sm:p-4 text-right">Expense</th>
                        <th class="p-2 sm:p-4 text-right">Cash Flow</th>
                    </tr>
                </thead>
                <tbody v-if="!loading" class="divide-y divide-gray-200/50 dark:divide-gray-600/50">
                    <tr
                        v-for="item in cashFlowData"
                        :key="item.month"
                        class="hover:bg-white/50 dark:hover:bg-gray-700/30 transition-colors duration-200"
                    >
                        <td class="p-2 sm:p-4 font-medium text-gray-900 dark:text-white text-sm">
                            {{ months[item.month - 1].substring(0, 3) }}
                        </td>
                        <td class="p-2 sm:p-4 text-right text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                            <span class="block truncate max-w-[80px] sm:max-w-none" :title="formatCurrency(item.income)">
                                {{ formatCurrency(item.income) }}
                            </span>
                        </td>
                        <td class="p-2 sm:p-4 text-right text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                            <span class="block truncate max-w-[80px] sm:max-w-none" :title="formatCurrency(item.expense)">
                                {{ formatCurrency(item.expense) }}
                            </span>
                        </td>
                        <td class="p-2 sm:p-4 text-right text-sm">
                            <span
                                :class="[
                                    'font-bold px-2 sm:px-3 py-1 rounded-full text-xs block truncate max-w-[90px] sm:max-w-none',
                                    item.cash_flow >= 0
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                ]"
                                :title="formatCurrency(item.cash_flow)"
                            >
                                {{ formatCurrency(item.cash_flow) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr v-for="i in 12" :key="i">
                        <td class="p-2 sm:p-3 lg:p-4">
                            <div class="h-3 w-8 sm:w-12 animate-pulse rounded bg-muted"></div>
                        </td>
                        <td class="p-2 sm:p-3 lg:p-4 text-right">
                            <div class="h-3 w-10 sm:w-12 animate-pulse rounded bg-muted ml-auto"></div>
                        </td>
                        <td class="p-2 sm:p-3 lg:p-4 text-right">
                            <div class="h-3 w-10 sm:w-12 animate-pulse rounded bg-muted ml-auto"></div>
                        </td>
                        <td class="p-2 sm:p-3 lg:p-4 text-right">
                            <div class="h-3 w-10 sm:w-12 animate-pulse rounded bg-muted ml-auto"></div>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 border-t border-gray-200/50 dark:border-gray-600/50">
                    <tr class="font-bold text-sm">
                        <td class="p-2 sm:p-4 text-gray-900 dark:text-white">Total</td>
                        <td class="p-2 sm:p-4 text-right text-gray-900 dark:text-white text-xs sm:text-sm">
                            <span class="block truncate max-w-[80px] sm:max-w-none" :title="formatCurrency(totalIncome)">
                                {{ formatCurrency(totalIncome) }}
                            </span>
                        </td>
                        <td class="p-2 sm:p-4 text-right text-gray-900 dark:text-white text-xs sm:text-sm">
                            <span class="block truncate max-w-[80px] sm:max-w-none" :title="formatCurrency(totalExpense)">
                                {{ formatCurrency(totalExpense) }}
                            </span>
                        </td>
                        <td class="p-2 sm:p-4 text-right">
                            <span
                                :class="[
                                    'font-bold px-2 sm:px-4 py-1 sm:py-2 rounded-full text-xs sm:text-sm block truncate max-w-[90px] sm:max-w-none',
                                    totalCashFlow >= 0
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                ]"
                                :title="formatCurrency(totalCashFlow)"
                            >
                                {{ formatCurrency(totalCashFlow) }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>