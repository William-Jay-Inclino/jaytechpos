<script setup lang="ts">
import { ref } from 'vue'

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
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'This Week' },
    { key: 'month', label: 'This Month' },
    { key: 'year', label: 'This Year' },
] as const
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Best Selling Products</h3>
        </div>
        
        <!-- Tabs -->
        <div class="mb-4 flex space-x-1 rounded-lg bg-muted p-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'flex-1 rounded-md px-3 py-2 text-sm font-medium transition-all',
                    activeTab === tab.key
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                ]"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Products List -->
        <div class="space-y-3">
            <div
                v-for="(product, index) in products[activeTab]"
                :key="product.id"
                class="flex items-center justify-between rounded-lg border border-border/50 p-3"
            >
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                    >
                        {{ index + 1 }}
                    </div>
                    <div>
                        <p class="font-medium">{{ product.product_name }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold">{{ product.total_sold }} sold</p>
                </div>
            </div>
            
            <div v-if="products[activeTab].length === 0" class="py-8 text-center text-muted-foreground">
                No products sold {{ activeTab === 'today' ? 'today' : activeTab === 'week' ? 'this week' : activeTab === 'month' ? 'this month' : 'this year' }}
            </div>
        </div>
    </div>
</template>