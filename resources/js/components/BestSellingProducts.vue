<script setup lang="ts">
import { ref } from 'vue'
import { 
    TrendingUp, 
    Calendar, 
    CalendarDays, 
    CalendarRange, 
    CalendarHeart,
    Trophy,
    Medal,
    Award,
    Star,
    Bookmark,
    Heart,
    ThumbsUp,
    CheckCircle,
    Package,
    PackageX
} from 'lucide-vue-next'

interface Product {
    id: number
    product_name: string
    total_sold: number
}

interface Props {
    products: {
        today: Product[]
        week: Product[]
        month: Product[]
        year: Product[]
    }
}

defineProps<Props>()

const activeTab = ref<'today' | 'week' | 'month' | 'year'>('today')

const tabs = [
    { key: 'today', label: 'Today', icon: Calendar },
    { key: 'week', label: 'This Week', icon: CalendarDays },
    { key: 'month', label: 'This Month', icon: CalendarRange },
    { key: 'year', label: 'This Year', icon: CalendarHeart },
] as const

const getRankingIcon = (index: number) => {
    if (index === 0) return Trophy
    if (index === 1) return Medal
    if (index === 2) return Award
    if (index === 3) return Star
    if (index === 4) return Bookmark
    if (index === 5) return Heart
    if (index === 6) return ThumbsUp
    return CheckCircle
}

const getRankingColor = (index: number) => {
    if (index === 0) return 'text-yellow-500 bg-yellow-500/10 border-yellow-500/20'
    if (index === 1) return 'text-gray-400 bg-gray-400/10 border-gray-400/20'
    if (index === 2) return 'text-orange-500 bg-orange-500/10 border-orange-500/20'
    if (index === 3) return 'text-blue-500 bg-blue-500/10 border-blue-500/20'
    if (index === 4) return 'text-purple-500 bg-purple-500/10 border-purple-500/20'
    if (index === 5) return 'text-pink-500 bg-pink-500/10 border-pink-500/20'
    if (index === 6) return 'text-green-500 bg-green-500/10 border-green-500/20'
    return 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20'
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-white/20 bg-white/70 backdrop-blur-xl shadow-xl dark:border-gray-700/50 dark:bg-gray-800/70 p-6">
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500/20 to-pink-500/20">
                    <TrendingUp class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="text-lg font-semibold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                    Best Selling Products
                </h3>
            </div>
        </div>
        
        <!-- Enhanced Tabs -->
        <div class="mb-6 p-1 rounded-xl bg-gray-100/50 dark:bg-gray-700/50 backdrop-blur-sm">
            <div class="flex flex-col gap-1 sm:flex-row">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium transition-all duration-300 sm:flex-1',
                        activeTab === tab.key
                            ? 'bg-white text-purple-600 shadow-lg dark:bg-gray-800 dark:text-purple-400 transform scale-105'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-white/50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700/50'
                    ]"
                >
                    <component :is="tab.icon" class="h-4 w-4" />
                    <span>{{ tab.label }}</span>
                </button>
            </div>
        </div>

        <!-- Enhanced Products List -->
        <div class="space-y-3">
            <div
                v-for="(product, index) in products[activeTab]"
                :key="product.id"
                :class="[
                    'group relative overflow-hidden rounded-xl border p-4 transition-all duration-300 hover:shadow-lg hover:scale-102',
                    index < 3 
                        ? 'border-purple-200/50 bg-gradient-to-r from-purple-50/50 to-pink-50/50 dark:border-purple-700/50 dark:from-purple-900/20 dark:to-pink-900/20' 
                        : 'border-gray-200/50 bg-white/50 hover:border-gray-300/50 dark:border-gray-600/50 dark:bg-gray-700/50'
                ]"
            >
                <!-- Subtle glow effect for top 3 -->
                <div v-if="index < 3" class="absolute inset-0 bg-gradient-to-r from-purple-400/5 to-pink-400/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                
                <div class="relative flex items-center justify-between">
                    <div class="flex items-center space-x-4 min-w-0 flex-1">
                        <div
                            :class="[
                                'flex h-12 w-12 items-center justify-center rounded-full border-2 transition-all duration-300 flex-shrink-0 group-hover:scale-110',
                                getRankingColor(index)
                            ]"
                        >
                            <component :is="getRankingIcon(index)" class="h-5 w-5" />
                        </div>
                        <div class="flex flex-col min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white truncate text-base">
                                {{ product.product_name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Rank #{{ index + 1 }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-4">
                        <div class="flex items-center gap-2 justify-end mb-1">
                            <Package class="h-4 w-4 text-gray-400" />
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ product.total_sold }}
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">units sold</p>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Empty State -->
            <div v-if="products[activeTab].length === 0" class="py-16 text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                    <PackageX class="h-10 w-10 text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    No products sold
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ activeTab === 'today' ? 'today' : activeTab === 'week' ? 'this week' : activeTab === 'month' ? 'this month' : 'this year' }}
                </p>
            </div>
        </div>
    </div>
</template>