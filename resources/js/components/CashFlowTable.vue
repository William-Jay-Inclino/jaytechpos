<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

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

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

const years = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYear - i)
})

const totalIncome = computed(() => 
    props.data.reduce((sum, item) => sum + item.income, 0)
)

const totalExpense = computed(() => 
    props.data.reduce((sum, item) => sum + item.expense, 0)
)

const totalCashFlow = computed(() => 
    props.data.reduce((sum, item) => sum + item.cash_flow, 0)
)

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value)
}

function updateYear() {
    loading.value = true
    router.get('/api/dashboard/cash-flow', 
        { year: selectedYear.value },
        {
            preserveState: true,
            onFinish: () => loading.value = false
        }
    )
}
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
        <div class="border-b border-border/50 p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Cash Flow Analysis</h3>
                <div class="flex items-center space-x-2">
                    <label for="year" class="text-sm font-medium">Year:</label>
                    <select
                        id="year"
                        v-model="selectedYear"
                        @change="updateYear"
                        class="rounded-md border border-input bg-background px-3 py-1.5 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-border/50">
                    <tr class="text-left text-sm font-medium text-muted-foreground">
                        <th class="p-4">Month</th>
                        <th class="p-4 text-right">Income</th>
                        <th class="p-4 text-right">Expense</th>
                        <th class="p-4 text-right">Cash Flow</th>
                    </tr>
                </thead>
                <tbody v-if="!loading" class="divide-y divide-border/50">
                    <tr
                        v-for="item in data"
                        :key="item.month"
                        class="hover:bg-muted/50"
                    >
                        <td class="p-4 font-medium">
                            {{ months[item.month - 1] }}
                        </td>
                        <td class="p-4 text-right">
                            {{ formatCurrency(item.income) }}
                        </td>
                        <td class="p-4 text-right">
                            {{ formatCurrency(item.expense) }}
                        </td>
                        <td class="p-4 text-right">
                            <span
                                :class="[
                                    'font-medium',
                                    item.cash_flow >= 0
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-600 dark:text-red-400'
                                ]"
                            >
                                {{ formatCurrency(item.cash_flow) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr v-for="i in 12" :key="i">
                        <td class="p-4">
                            <div class="h-4 w-20 animate-pulse rounded bg-muted"></div>
                        </td>
                        <td class="p-4 text-right">
                            <div class="h-4 w-16 animate-pulse rounded bg-muted ml-auto"></div>
                        </td>
                        <td class="p-4 text-right">
                            <div class="h-4 w-16 animate-pulse rounded bg-muted ml-auto"></div>
                        </td>
                        <td class="p-4 text-right">
                            <div class="h-4 w-16 animate-pulse rounded bg-muted ml-auto"></div>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="border-t border-border/50 bg-muted/50">
                    <tr class="font-semibold">
                        <td class="p-4">Total</td>
                        <td class="p-4 text-right">{{ formatCurrency(totalIncome) }}</td>
                        <td class="p-4 text-right">{{ formatCurrency(totalExpense) }}</td>
                        <td class="p-4 text-right">
                            <span
                                :class="[
                                    totalCashFlow >= 0
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-600 dark:text-red-400'
                                ]"
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