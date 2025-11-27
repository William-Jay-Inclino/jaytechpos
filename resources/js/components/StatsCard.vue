<script setup lang="ts">
import Icon from './Icon.vue'
import { ref, onMounted, watch } from 'vue'
import { formatCurrency } from '@/utils/currency'

interface Props {
    title: string
    value: string | number
    trend?: {
        value: string
        isPositive: boolean
    }
    loading?: boolean
}

const props = defineProps<Props>()

const animatedValue = ref(0)
const isAnimating = ref(false)

// Helper function to determine if value should show celebration
function shouldShowCelebration(value: string | number): boolean {
    const num = typeof value === 'string' ? parseFloat(value) : value
    return num > 0
}

// Animation function for counting effect
function animateValue(targetValue: number, duration: number = 2000) {
    if (isAnimating.value) return
    
    isAnimating.value = true
    const startValue = 0
    const startTime = performance.now()
    
    function updateValue(currentTime: number) {
        const elapsed = currentTime - startTime
        const progress = Math.min(elapsed / duration, 1)
        
        // Use easeOutQuart easing for smooth deceleration
        const easeProgress = 1 - Math.pow(1 - progress, 4)
        
        animatedValue.value = Math.floor(startValue + (targetValue - startValue) * easeProgress)
        
        if (progress < 1) {
            requestAnimationFrame(updateValue)
        } else {
            animatedValue.value = targetValue
            isAnimating.value = false
        }
    }
    
    requestAnimationFrame(updateValue)
}

// Function to get numeric value for animation
function getNumericValue(value: string | number): number {
    return typeof value === 'string' ? parseFloat(value) || 0 : value
}

// Start animation when component mounts or value changes
onMounted(() => {
    if (!props.loading && typeof props.value === 'number') {
        animateValue(getNumericValue(props.value))
    }
})

watch(() => props.value, (newValue) => {
    if (!props.loading && typeof newValue === 'number') {
        animateValue(getNumericValue(newValue))
    }
}, { immediate: false })

watch(() => props.loading, (isLoading) => {
    if (!isLoading && typeof props.value === 'number') {
        // Reset and animate when loading finishes
        animatedValue.value = 0
        setTimeout(() => animateValue(getNumericValue(props.value)), 100)
    }
})
</script>

<template>
    <div class="group relative overflow-hidden rounded-3xl border border-white/20 bg-white/80 backdrop-blur-xl shadow-2xl dark:border-gray-700/30 dark:bg-gray-800/80 p-8 sm:p-10 lg:p-12 hover:shadow-3xl transition-all duration-700">
        <!-- Enhanced gradient overlay with animation -->
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 via-emerald-500/5 to-teal-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
        
        <!-- Floating decoration elements -->
        <div class="absolute top-6 right-6 w-20 h-20 bg-gradient-to-br from-green-400/20 to-emerald-400/20 rounded-full blur-xl opacity-50 group-hover:opacity-75 transition-opacity duration-500"></div>
        <div class="absolute bottom-6 left-6 w-16 h-16 bg-gradient-to-tr from-emerald-400/20 to-teal-400/20 rounded-full blur-xl opacity-30 group-hover:opacity-60 transition-opacity duration-500"></div>
        
        <!-- Content -->
        <div class="relative">
            <div class="text-center space-y-6">
                <!-- Title with enhanced styling -->
                <div class="space-y-2">
                    <div class="flex justify-center items-center gap-2 mb-4">
                        <div class="w-12 h-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full"></div>
                        <span class="text-2xl">💰</span>
                        <div class="w-12 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"></div>
                    </div>
                    <p class="text-lg sm:text-xl font-semibold text-gray-700 dark:text-gray-300 tracking-wide">
                        {{ title }}
                    </p>
                </div>

                <!-- Value display with enhanced presentation -->
                <div v-if="loading" class="flex justify-center">
                    <div class="h-20 w-80 animate-pulse rounded-2xl bg-gray-200 dark:bg-gray-700"></div>
                </div>
                <div v-else class="space-y-4">
                    <div class="relative">
                        <!-- Main value with animation -->
                        <p class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-400 dark:via-emerald-400 dark:to-teal-400 bg-clip-text text-transparent leading-none transform group-hover:scale-105 transition-transform duration-500">
                            {{ formatCurrency(animatedValue) }}
                        </p>
                        
                        <!-- Celebration icons with animation -->
                        <div v-if="shouldShowCelebration(value)" class="flex justify-center items-center gap-2 mt-4">
                            <span class="text-4xl animate-bounce" style="animation-delay: 0ms">🎉</span>
                            <span class="text-3xl animate-pulse" style="animation-delay: 200ms">✨</span>
                            <span class="text-4xl animate-bounce" style="animation-delay: 400ms">💰</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>