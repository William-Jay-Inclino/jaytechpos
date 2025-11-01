<script setup lang="ts">
import Icon from './Icon.vue'

interface Props {
    title: string
    value: string | number
    icon?: string
    trend?: {
        value: string
        isPositive: boolean
    }
    loading?: boolean
}

defineProps<Props>()

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(num)
}
</script>

<template>
    <div
        class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
    >
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">
                    {{ title }}
                </p>
                <div v-if="loading" class="mt-2 h-8 w-32 animate-pulse rounded bg-muted"></div>
                <p v-else class="mt-2 text-3xl font-bold">
                    {{ typeof value === 'number' && title.toLowerCase().includes('amount') || title.toLowerCase().includes('sales') || title.toLowerCase().includes('cash') || title.toLowerCase().includes('payments') || title.toLowerCase().includes('receivable') ? formatCurrency(value) : value }}
                </p>
            </div>
            <div v-if="icon" class="opacity-80">
                <Icon :name="icon" class="h-8 w-8" />
            </div>
        </div>
        <div v-if="trend" class="mt-4 flex items-center text-sm">
            <span
                :class="[
                    'flex items-center',
                    trend.isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                ]"
            >
                <Icon 
                    :name="trend.isPositive ? 'trending-up' : 'trending-down'" 
                    class="mr-1 h-4 w-4" 
                />
                {{ trend.value }}
            </span>
        </div>
    </div>
</template>