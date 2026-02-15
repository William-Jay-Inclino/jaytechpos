<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { formatCurrency } from '@/utils/currency'

interface Props {
    title: string
    cashValue: number
    utangValue: number
    trend?: {
        value: string
        isPositive: boolean
    }
    loading?: boolean
}

const props = defineProps<Props>()

const animatedValue = ref(0)
const isAnimating = ref(false)

// Filter state — multi-select, default is cash only
const showCash = ref(true)
const showUtang = ref(false)

function toggleCash() {
    // Don't allow deselecting both
    if (showCash.value && !showUtang.value) return
    showCash.value = !showCash.value
}

function toggleUtang() {
    // Don't allow deselecting both
    if (showUtang.value && !showCash.value) return
    showUtang.value = !showUtang.value
}

// Computed display value based on filter selection
const displayValue = computed(() => {
    let total = 0
    if (showCash.value) total += props.cashValue
    if (showUtang.value) total += props.utangValue
    return total
})

// Computed label based on filter selection
const filterLabel = computed(() => {
    if (showCash.value && showUtang.value) return 'all'
    if (showUtang.value) return 'utang'
    return 'cash'
})

// Helper function to determine if value should show celebration
function shouldShowCelebration(value: number): boolean {
    return value > 0
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

// Re-animate when filter changes
function reAnimate() {
    animatedValue.value = 0
    isAnimating.value = false
    setTimeout(() => animateValue(displayValue.value), 50)
}

// Start animation when component mounts
onMounted(() => {
    if (!props.loading) {
        animateValue(displayValue.value)
    }
})

// Re-animate when the underlying data changes
watch(displayValue, () => {
    if (!props.loading) {
        reAnimate()
    }
})

watch(() => props.loading, (isLoading) => {
    if (!isLoading) {
        animatedValue.value = 0
        setTimeout(() => animateValue(displayValue.value), 100)
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
                    <!-- Subtle filter toggle -->
                    <div class="flex justify-center items-center gap-1.5 pt-2">
                        <button
                            @click="toggleCash"
                            :class="[
                                'px-3 py-1 text-xs font-medium rounded-full transition-all duration-300',
                                showCash
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-400 dark:bg-gray-700/40 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700/60',
                            ]"
                        >
                            Cash
                        </button>
                        <button
                            @click="toggleUtang"
                            :class="[
                                'px-3 py-1 text-xs font-medium rounded-full transition-all duration-300',
                                showUtang
                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                    : 'bg-gray-100 text-gray-400 dark:bg-gray-700/40 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700/60',
                            ]"
                        >
                            Utang
                        </button>
                    </div>
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
                        <div v-if="shouldShowCelebration(displayValue)" class="flex justify-center items-center gap-2 mt-4">
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