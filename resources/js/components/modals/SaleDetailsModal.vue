<script setup lang="ts">
import { Sale, CustomerTransaction } from '@/types/pos';
import { formatPhilippinePeso, formatManilaDateTime } from '@/utils/timezone';
import { computed } from 'vue';
import { X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    open: boolean;
    transaction: CustomerTransaction | null;
    sale: Sale | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const handleClose = () => {
    emit('update:open', false);
};

const formatAmountLocal = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined || amount === '') return '';
    if (typeof amount === 'number') return formatPhilippinePeso(amount);
    const cleaned = String(amount).replace(/[,₱\s]/g, '');
    const parsed = parseFloat(cleaned);
    return !isNaN(parsed) ? formatPhilippinePeso(parsed) : String(amount);
};

const formatDate = computed(() => {
    if (!props.sale) return '';
    return formatManilaDateTime(props.sale.transaction_date ?? props.sale.created_at ?? '');
});

const changeAmountComputed = computed<number | null>(() => {
    const s = props.sale;
    if (!s) return null;

    // Prefer backend-provided value when available
    if (s.change_amount !== undefined && s.change_amount !== null) {
        return Number(s.change_amount);
    }

    // Fallback: compute client-side to match backend formula
    // change_amount = max(0, amount_tendered - total_amount - deduct_from_balance)
    const total = Number(s.total_amount ?? 0);
    const amountReceived = Number(s.amount_tendered ?? 0);
    const deduct = Number(s.deduct_from_balance ?? 0);

    const computed = amountReceived - total - deduct;
    if (!Number.isFinite(computed)) return null;
    return Math.max(0, computed);
});
</script>

<template>
    <!-- Simple Modal Overlay -->
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="handleClose"
    >
        <div
            class="relative w-full max-w-lg max-h-[85vh] flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden"
        >
            <!-- Header -->
            <div class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 p-6 pb-4 text-center">
                <button
                    @click="handleClose"
                    class="absolute right-4 top-4 rounded-sm opacity-70 hover:opacity-100 transition-opacity"
                >
                    <X class="h-4 w-4" />
                </button>
                <h2 class="text-xl font-semibold">Receipt #{{ sale?.invoice_number || 'N/A' }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ formatDate }}</p>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div v-if="sale" class="space-y-4">
                    <!-- Items Purchased -->
                    <div v-if="sale.items && sale.items.length > 0" class="space-y-3">
                        <h4 class="text-sm font-medium tracking-wide text-gray-600 dark:text-gray-400 uppercase">
                            Items Sold
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="item in sale.items"
                                :key="item.id"
                                class="flex items-center justify-between py-2"
                            >
                                <div class="flex-1">
                                    <div class="text-sm font-medium">
                                        {{ item.product_name }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ item.quantity }} × {{ formatPhilippinePeso(item.unit_price) }}
                                    </div>
                                </div>
                                <div class="text-sm font-medium">
                                    {{ formatPhilippinePeso(item.total_amount) }}
                                </div>
                            </div>
                            
                            <!-- Total Amount Summary Row -->
                            <div class="flex items-center justify-between border-t-2 border-gray-300 dark:border-gray-600 pt-3 mt-2">
                                <div class="text-sm font-bold">Total Amount</div>
                                <div class="text-sm font-bold">
                                    {{ formatPhilippinePeso(sale.total_amount || 0) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details Section -->
                    <div class="space-y-2 rounded-lg bg-gray-100 dark:bg-gray-700 p-3">
                        <h4 class="text-sm font-medium tracking-wide text-gray-600 dark:text-gray-400 uppercase">
                            Payment Details
                        </h4>

                        <!-- Cash Payment Details -->
                        <div v-if="sale.payment_type === 'cash'" class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                                <span class="font-medium">💵 Cash</span>
                            </div>
                            <div v-if="sale.amount_tendered" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount Tendered:</span>
                                <span>{{ formatPhilippinePeso(sale.amount_tendered) }}</span>
                            </div>
                            <div v-if="sale.deduct_from_balance && sale.deduct_from_balance > 0" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Deduct to Balance:</span>
                                <span>{{ formatPhilippinePeso(sale.deduct_from_balance) }}</span>
                            </div>
                            <div v-if="sale.payment_type === 'cash' && changeAmountComputed !== null" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Change:</span>
                                <span>{{ formatPhilippinePeso(changeAmountComputed || 0) }}</span>
                            </div>
                        </div>

                        <!-- Utang Payment Details -->
                        <div v-else-if="sale.payment_type === 'utang'" class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                                <span class="font-medium">📝 Credit (Utang)</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount Paid:</span>
                                <span>{{ formatPhilippinePeso(sale.paid_amount || 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Balance Summary -->
                    <div v-if="(props.transaction as any)?.previous_balance !== undefined && (props.transaction as any)?.new_balance !== undefined" class="space-y-2 rounded-lg bg-gray-100 dark:bg-gray-700 p-3">
                        <h4 class="text-sm font-medium tracking-wide text-gray-600 dark:text-gray-400 uppercase">
                            Customer Balance
                        </h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Previous:</span>
                                <span>{{ formatAmountLocal((props.transaction as any).previous_balance ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-1 font-medium">
                                <span>New Balance:</span>
                                <span class="text-red-600 dark:text-red-400">{{ formatAmountLocal((props.transaction as any).new_balance ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="sale?.notes && sale.notes.trim()" class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3">
                        <h4 class="mb-1 text-sm font-medium text-blue-800 dark:text-blue-200">Notes:</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300">{{ sale.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 p-4 flex justify-center">
                <Button
                    @click="handleClose"
                    class="px-8"
                >
                    Close
                </Button>
            </div>
        </div>
    </div>
</template>
