<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import StockMovementModal from '@/components/modals/StockMovementModal.vue';
import { showErrorToast, showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, Eye, Info, Loader2, Pencil, Save, Search } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

type ActionType = 'stock-in' | 'stock-out' | 'stock-adjustment';

interface Product {
    id: number;
    product_name: string;
    quantity: string;
    low_stock_threshold: string;
}

interface PaginatedProducts {
    data: Product[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
}

const props = defineProps<{
    products: PaginatedProducts
    unstockedProducts: { id: number; product_name: string }[]
    filters: {
        search?: string
    }
}>();

const localProducts = ref<Product[]>([...props.products.data]);

// Keep localProducts in sync when server data changes (pagination/search)
watch(() => props.products.data, (newData) => {
    localProducts.value = [...newData];
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory',
        href: '/inventory',
    },
];

const selectedAction = ref<ActionType>('stock-in');
const searchQuery = ref(props.filters.search || '');
const actionQuantities = ref<Record<number, string>>({});
const savingProducts = ref<Record<number, boolean>>({});
const showMovementModal = ref(false);
const selectedProductId = ref<number | null>(null);
const selectedProductName = ref<string>('');
const editingLSA = ref<number | null>(null);
const lsaEditValue = ref<string>('');
const savingLSA = ref(false);
const showUnstockedModal = ref(false);

// Loading state
const isLoading = ref(false)
let removeStartListener: (() => void) | null = null
let removeFinishListener: (() => void) | null = null

onMounted(() => {
    removeStartListener = router.on('start', () => {
        isLoading.value = true
    })
    removeFinishListener = router.on('finish', () => {
        isLoading.value = false
    })
})

onUnmounted(() => {
    removeStartListener?.()
    removeFinishListener?.()
})

// Debounced server-side search
let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }

    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 300)
})

function applyFilters(): void {
    router.get('/inventory', {
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

function clearSearch(): void {
    searchQuery.value = ''
    applyFilters()
}

const helperText = computed(() => {
    switch (selectedAction.value) {
        case 'stock-in':
            return 'Add stock to your inventory. Enter the quantity to add for each product.';
        case 'stock-out':
            return 'Remove stock from your inventory. Enter the quantity to remove for each product.';
        case 'stock-adjustment':
            return 'Adjust stock to an exact quantity. Enter the new total quantity for each product.';
        default:
            return '';
    }
});

const actionColumnTxt = computed(() => {
    switch (selectedAction.value) {
        case 'stock-in':
            return 'Add Stock';
        case 'stock-out':
            return 'Remove Stock';
        case 'stock-adjustment':
            return 'Update Qty';
        default:
            return '';
    }
})

async function handleSave(productId: number) {
    const quantity = actionQuantities.value[productId];
    if (!quantity || parseFloat(quantity) <= 0) {
        showErrorToast('Please enter a valid quantity');
        return;
    }

    const endpoint = getEndpoint();
    if (!endpoint) return;

    savingProducts.value[productId] = true;

    try {
        const response = await axios.post(endpoint, {
            product_id: productId,
            quantity: parseFloat(quantity),
        });

        console.log('response', response.data);

        if (response.data.success && response.data.product) {
            showSuccessToast(response.data.message);
            actionQuantities.value[productId] = '';
            
            // Update the specific product in the local array
            const index = localProducts.value.findIndex(p => p.id === productId);
            if (index !== -1) {
                localProducts.value[index] = response.data.product;
            }
        }
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Failed to update inventory';
        showErrorToast(errorMessage);
    } finally {
        savingProducts.value[productId] = false;
    }
}

function getEndpoint(): string {
    switch (selectedAction.value) {
        case 'stock-in':
            return '/api/stock-movements/stock-in';
        case 'stock-out':
            return '/api/stock-movements/stock-out';
        case 'stock-adjustment':
            return '/api/stock-movements/adjustment';
        default:
            return '';
    }
}

// function isLowStock(product: Product): boolean {
//     return Number(product.quantity) <= Number(product.low_stock_threshold);
// }

function openMovementModal(productId: number, productName: string) {
    selectedProductId.value = productId;
    selectedProductName.value = productName;
    showMovementModal.value = true;
}
function isSaving(productId: number): boolean {
    return savingProducts.value[productId] || false;
}

function startEditLSA(productId: number, currentThreshold: number) {
    editingLSA.value = productId;
    lsaEditValue.value = currentThreshold.toString();
}

function cancelEditLSA() {
    editingLSA.value = null;
    lsaEditValue.value = '';
}

async function saveLSA(productId: number) {
    const threshold = lsaEditValue.value;
    if (!threshold || parseFloat(threshold) < 0) {
        showErrorToast('Please enter a valid threshold');
        return;
    }

    savingLSA.value = true;

    try {
        const response = await axios.post('/api/inventory/update-low-stock-threshold', {
            product_id: productId,
            low_stock_threshold: parseFloat(threshold),
        });

        if (response.data.success && response.data.product) {
            showSuccessToast(response.data.message);
            
            // Update the specific product in the local array
            const index = localProducts.value.findIndex(p => p.id === productId);
            if (index !== -1) {
                localProducts.value[index] = response.data.product;
            }
            
            cancelEditLSA();
        }
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Failed to update threshold';
        showErrorToast(errorMessage);
    } finally {
        savingLSA.value = false;
    }
}
</script>

<template>
    <Head title="Inventory" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl space-y-6">

                <!-- Action Toggle Buttons -->
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <Button
                            size="lg"
                            class="flex-1 min-w-0"
                            :variant="selectedAction === 'stock-in' ? 'default' : 'outline'"
                            @click="selectedAction = 'stock-in'"
                        >
                            <span class="md:hidden">IN</span>
                            <span class="hidden md:inline">Stock In</span>
                        </Button>
                        <Button
                            size="lg"
                            class="flex-1 min-w-0"
                            :variant="selectedAction === 'stock-out' ? 'default' : 'outline'"
                            @click="selectedAction = 'stock-out'"
                        >
                            <span class="md:hidden">OUT</span>
                            <span class="hidden md:inline">Stock Out</span>
                        </Button>
                        <Button
                            size="lg"
                            class="flex-1 min-w-0"
                            :variant="selectedAction === 'stock-adjustment' ? 'default' : 'outline'"
                            @click="selectedAction = 'stock-adjustment'"
                        >
                            <span class="md:hidden">ADJUSTMENT</span>
                            <span class="hidden md:inline">Stock Adjustment</span>
                        </Button>
                    </div>

                    <!-- Helper Text -->
                    <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                        {{ helperText }}
                    </div>
                </div>

                <!-- Search -->
                <div class="relative">
                    <Search v-if="!isLoading" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Loader2 v-else class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 animate-spin" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search here..."
                        class="pl-9"
                    />
                </div>

                <!-- Unstocked Products Warning -->
                <div v-if="unstockedProducts.length > 0" class="flex items-center justify-between rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                    <span>{{ unstockedProducts.length }} {{ unstockedProducts.length === 1 ? 'product has' : 'products have' }} no inventory record yet.</span>
                    <Button
                        variant="outline"
                        size="sm"
                        class="shrink-0 border-amber-300 text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/40"
                        @click="showUnstockedModal = true"
                    >
                        <Eye class="h-4 w-4" />
                        View
                    </Button>
                </div>

                <!-- Products Table -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 pl-6 pr-4 text-left text-sm font-medium text-gray-900 dark:text-white">
                                    Product
                                </th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-900 dark:text-white text-nowrap">
                                    Available Qty
                                </th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-1">
                                        <span>LSA</span>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Info class="h-4 w-4 text-gray-400" />
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Low Stock Alert</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-900 dark:text-white text-nowrap">
                                    {{ actionColumnTxt }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in localProducts"
                                :key="product.id"
                                class="group border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <td class="relative py-4 pl-6 pr-4 font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center h-full">
                                        <span :class="{'text-red-500 dark:text-red-300': Number(product.quantity) <= 0}">
                                            {{ product.product_name }}
                                        </span>
                                    </div>

                                    <!-- Hover-only action -->
                                    <button
                                        class="absolute left-6 bottom-1
                                            text-sm text-blue-500 dark:text-blue-300
                                            opacity-0 group-hover:opacity-100
                                            transition-opacity duration-200 hover:underline"
                                        @click="openMovementModal(product.id, product.product_name)"
                                    >
                                        View stock movement
                                    </button>
                                </td>

                                <td class="py-4 px-4 text-gray-900 dark:text-white">
                                    {{ product.quantity }}
                                </td>
                                <td class="py-4 px-4">
                                    <div v-if="editingLSA === product.id" class="flex items-center gap-2">
                                        <Input
                                            v-model="lsaEditValue"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-20"
                                            :disabled="savingLSA"
                                            @keyup.enter="saveLSA(product.id)"
                                            @keyup.escape="cancelEditLSA"
                                        />
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8 cursor-pointer"
                                            :disabled="savingLSA"
                                            @click="saveLSA(product.id)"
                                        >
                                            <Check class="h-4 w-4 text-green-600" />
                                        </Button>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <span class="text-gray-900 dark:text-white">{{ product.low_stock_threshold }}</span>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-6 w-6 cursor-pointer opacity-0 transition-opacity group-hover:opacity-100"
                                            @click="startEditLSA(product.id, Number(product.low_stock_threshold))"
                                        >
                                            <Pencil class="h-3 w-3 text-gray-400" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <Input
                                            v-model="actionQuantities[product.id]"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-28"
                                            :disabled="isSaving(product.id)"
                                        />
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="cursor-pointer"
                                            :disabled="!actionQuantities[product.id] || isSaving(product.id)"
                                            @click="handleSave(product.id)"
                                        >
                                            <Save class="h-4 w-4 text-primary" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="localProducts.length === 0">
                                <td colspan="4" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <template v-if="searchQuery">
                                        No products found. <button class="text-blue-500 hover:underline" @click="clearSearch">Clear search</button>
                                    </template>
                                    <template v-else>
                                        No products found
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="localProducts.length" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ products.from }} to {{ products.to }} of {{ products.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="products.current_page === 1"
                                @click="router.get('/inventory', { page: products.current_page - 1, search: searchQuery || undefined }, { preserveState: true, preserveScroll: true })"
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="products.current_page === products.last_page"
                                @click="router.get('/inventory', { page: products.current_page + 1, search: searchQuery || undefined }, { preserveState: true, preserveScroll: true })"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Stock Movement Modal -->
        <StockMovementModal
            v-model:open="showMovementModal"
            :product-id="selectedProductId"
            :product-name="selectedProductName"
        />

        <!-- Unstocked Products Modal -->
        <Dialog :open="showUnstockedModal" @update:open="showUnstockedModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Unstocked Products</DialogTitle>
                    <DialogDescription>
                        These products have no inventory record yet. Use Stock In to add initial stock.
                    </DialogDescription>
                </DialogHeader>
                <div class="max-h-80 overflow-y-auto">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li
                            v-for="product in unstockedProducts"
                            :key="product.id"
                            class="py-3 px-1 text-sm text-gray-900 dark:text-white"
                        >
                            {{ product.product_name }}
                        </li>
                    </ul>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

