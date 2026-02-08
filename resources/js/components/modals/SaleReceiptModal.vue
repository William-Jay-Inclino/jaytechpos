<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/utils/currency';
import {
    formatManilaDateTime,
    getCurrentManilaDateTime,
} from '@/utils/timezone';
import { X } from 'lucide-vue-next';

// Define the SaleResponse interface
interface SaleResponse {
    id: number;
    invoice_number: string;
    transaction_date: string;
    customer_id: number | null;
    customer_name: string | null;
    total_amount: number;
    paid_amount: number;
    amount_tendered?: number;
    change_amount?: number;
    balance_payment?: number;
    deduct_from_balance?: number;
    original_customer_balance?: number;
    new_customer_balance?: number;
    payment_type: string;
    is_utang: boolean;
    notes: string | null;
    cashier: string;
    items: Array<{
        id: number;
        product_id: number;
        product_name: string;
        quantity: number;
        unit_price: number;
        total_amount: number;
    }>;
    created_at?: string;
    updated_at?: string;
}

// Define props
const props = defineProps<{
    open: boolean;
    saleData: SaleResponse | null;
}>();

// Define emits
const emit = defineEmits<{
    'update:open': [value: boolean];
    close: [];
}>();

// Handle modal close
const handleClose = () => {
    emit('update:open', false);
    emit('close');
};
</script>

<template>
    <!-- Simple Modal Overlay -->
    <div
        v-if="open"
        data-testid="sale-receipt-modal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="handleClose"
    >
        <div
            class="relative w-full max-w-lg max-h-[85vh] flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-xl"
        >
            <!-- Header -->
            <div class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 p-6 pb-4 text-center">
                <button
                    @click="handleClose"
                    class="absolute right-4 top-4 rounded-sm opacity-70 hover:opacity-100 transition-opacity"
                >
                    <X class="h-4 w-4" />
                </button>
                <h2 class="text-xl font-semibold text-green-600 dark:text-green-400 flex items-center justify-center gap-2">
                    <span class="text-2xl">✅</span>
                    Sale Completed!
                </h2>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">

                <!-- Receipt Content -->
                <div v-if="saleData" class="space-y-4">
                    <!-- Items -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-medium tracking-wide text-gray-600 dark:text-gray-400 uppercase">
                            Items Sold
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="item in saleData.items"
                                :key="item.id"
                                class="flex items-center justify-between py-2"
                            >
                                <div class="flex-1">
                                    <div class="text-sm font-medium">
                                        {{ item.product_name }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                                    </div>
                                </div>
                                <div class="text-sm font-medium">
                                    {{ formatCurrency(item.total_amount) }}
                                </div>
                            </div>
                            
                            <!-- Total Amount Row -->
                            <div class="flex items-center justify-between border-t-2 border-gray-300 dark:border-gray-600 pt-3 mt-2">
                                <div class="text-sm font-bold">Total Amount</div>
                                <div class="text-sm font-bold">
                                    {{ formatCurrency(saleData.total_amount) }}
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
                        <div v-if="saleData.payment_type === 'cash'" class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                                <span class="font-medium">💵 Cash</span>
                            </div>
                            <div v-if="saleData.amount_tendered" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount Tendered:</span>
                                <span>{{ formatCurrency(saleData.amount_tendered) }}</span>
                            </div>
                            <div
                                v-if="saleData.deduct_from_balance && saleData.deduct_from_balance > 0"
                                class="flex justify-between"
                            >
                                <span class="text-gray-600 dark:text-gray-400">Deduct to Balance:</span>
                                <span>{{ formatCurrency(saleData.deduct_from_balance) }}</span>
                            </div>
                            <div
                                v-if="saleData.change_amount !== undefined && saleData.change_amount !== null"
                                class="flex justify-between"
                            >
                                <span class="text-gray-600 dark:text-gray-400">Change:</span>
                                <span>{{ formatCurrency(saleData.change_amount || 0) }}</span>
                            </div>
                        </div>

                        <!-- Utang Payment Details -->
                        <div v-else-if="saleData.payment_type === 'utang'" class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                                <span class="font-medium">📝 Credit (Utang)</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount Paid:</span>
                                <span>{{ formatCurrency(saleData.paid_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Balance (only if customer involved) -->
                    <div v-if="saleData.customer_id" class="space-y-2 rounded-lg bg-gray-100 dark:bg-gray-700 p-3">
                        <h4 class="text-sm font-medium tracking-wide text-gray-600 dark:text-gray-400 uppercase">
                            Customer Balance
                        </h4>
                        <div class="space-y-1 text-sm">
                            <div
                                v-if="saleData.original_customer_balance !== undefined"
                                class="flex justify-between"
                            >
                                <span class="text-gray-600 dark:text-gray-400">Previous:</span>
                                <span>{{ formatCurrency(saleData.original_customer_balance || 0) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-1 font-medium">
                                <span>New Balance:</span>
                                <span
                                    :class="(saleData.new_customer_balance || 0) > 0 ? 'text-red-600 dark:text-red-400' : ''"
                                >
                                    {{ formatCurrency(saleData.new_customer_balance || 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Message -->
                    <div class="rounded-lg bg-gray-100 dark:bg-gray-700 p-3 text-center">
                        <p class="text-sm font-medium">
                            Thank you for your business!
                        </p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                            {{ formatManilaDateTime(getCurrentManilaDateTime()) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 p-6 pt-4 flex justify-center">
                <Button
                    @click="handleClose"
                    data-testid="receipt-close-btn"
                    class="bg-green-600 px-8 text-white hover:bg-green-700"
                >
                    Close
                </Button>
            </div>
        </div>
    </div>
</template>
