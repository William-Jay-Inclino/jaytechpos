<script setup lang="ts">
import Icon from './Icon.vue'

interface Props {
    title: string
    value: string | number
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

// Helper function to determine if value should show celebration
function shouldShowCelebration(value: string | number): boolean {
    const num = typeof value === 'string' ? parseFloat(value) : value
    return num > 0
}
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 backdrop-blur-xl shadow-xl dark:border-gray-700/50 dark:bg-gray-800/70 p-6 hover:shadow-2xl transition-all duration-500">
        <!-- Gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent dark:from-gray-700/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        
        <!-- Content -->
        <div class="relative">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">
                        {{ title }}
                    </p>
                    <div v-if="loading" class="mt-2 h-8 w-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                    <div v-else class="flex items-center gap-3">
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-400 dark:to-emerald-400 bg-clip-text text-transparent">
                            {{ typeof value === 'number' && title.toLowerCase().includes('amount') || title.toLowerCase().includes('sales') || title.toLowerCase().includes('cash') || title.toLowerCase().includes('benta') || title.toLowerCase().includes('utang') || title.toLowerCase().includes('natanggap') || title.toLowerCase().includes('makolekta') || title.toLowerCase().includes('kumikita') ? formatCurrency(value) : value }}
                        </p>
                        <!-- Enhanced Celebration Icons -->
                        <div v-if="shouldShowCelebration(value)" class="flex items-center gap-1">
                            <span class="text-2xl">🎉</span>
                            <span class="text-xl">💰</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Trend indicator if exists -->
            <div v-if="trend" class="mt-4 flex items-center text-sm">
                <span
                    :class="[
                        'flex items-center px-2 py-1 rounded-full text-xs font-medium',
                        trend.isPositive 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' 
                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    ]"
                >
                    <Icon 
                        :name="trend.isPositive ? 'trending-up' : 'trending-down'" 
                        class="mr-1 h-3 w-3" 
                    />
                    {{ trend.value }}
                </span>
            </div>
        </div>
    </div>
</template>