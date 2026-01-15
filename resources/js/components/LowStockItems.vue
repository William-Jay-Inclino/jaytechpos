<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

interface LowStockProduct {
    product_id: number;
    product_name: string;
    quantity: string;
    low_stock_threshold: string;
}

interface Props {
    products: LowStockProduct[];
}

const props = defineProps<Props>();

const hasLowStockItems = computed(() => props.products && props.products.length > 0);

function getStockPercentage(quantity: string, threshold: string): number {
    const qty = parseFloat(quantity);
    const thresh = parseFloat(threshold);
    if (thresh === 0) return 100;
    return Math.min(100, (qty / thresh) * 100);
}

function getStockColor(percentage: number): string {
    if (percentage <= 25) return 'bg-red-500';
    if (percentage <= 50) return 'bg-orange-500';
    if (percentage <= 75) return 'bg-yellow-500';
    return 'bg-green-500';
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-white/20 bg-white/70 backdrop-blur-xl shadow-xl dark:border-gray-700/50 dark:bg-gray-800/70 p-4 sm:p-6">
        <div class="mb-4 sm:mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 sm:p-3 rounded-xl bg-gradient-to-br from-red-500/20 to-orange-500/20 flex-shrink-0">
                    <AlertTriangle class="h-4 w-4 sm:h-5 sm:w-5 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-base sm:text-lg font-semibold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent truncate">
                    Low Stock Alert
                </h3>
            </div>
        </div>
        
        <div>
            <div v-if="!hasLowStockItems" class="py-16 text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-green-100 to-green-200 dark:from-green-700 dark:to-green-800">
                    <span class="text-3xl">✓</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    All stocks are healthy
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No products below threshold
                </p>
            </div>

            <div v-else class="space-y-2 sm:space-y-3">
                <div
                    v-for="product in products"
                    :key="product.product_id"
                    class="relative overflow-hidden rounded-xl border border-gray-200/50 bg-white/50 p-3 sm:p-4 transition-all duration-300 hover:shadow-lg hover:scale-102 hover:border-red-200/50 dark:border-gray-600/50 dark:bg-gray-700/50 dark:hover:border-red-700/50"
                >
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ product.product_name }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Current: {{ product.quantity }} / Threshold: {{ product.low_stock_threshold }}
                            </p>
                        </div>
                        <div class="ml-4 text-right">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="
                                    parseFloat(product.quantity) === 0
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                        : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
                                "
                            >
                                {{ parseFloat(product.quantity) === 0 ? 'Out of Stock' : 'Low Stock' }}
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div
                            class="h-2 rounded-full transition-all duration-300"
                            :class="getStockColor(getStockPercentage(product.quantity, product.low_stock_threshold))"
                            :style="{ width: `${getStockPercentage(product.quantity, product.low_stock_threshold)}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
