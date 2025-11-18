<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Sale, CustomerTransaction } from '@/types/pos';
import { formatPhilippinePeso, formatManilaDateTime } from '@/utils/timezone';
import { computed } from 'vue';


const props = defineProps<{
    open: boolean;
    transaction: CustomerTransaction | null;
    sale: Sale | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

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
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
                <DialogHeader class="pb-2 text-center">
                <DialogTitle class="text-xl">Receipt #{{ sale?.invoice_number || 'N/A' }}</DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    {{ formatDate }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="sale" class="space-y-4">
                <!-- Items Purchased -->
                <div
                    v-if="sale.items && sale.items.length > 0"
                    class="space-y-3"
                >
                    <h4
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Items Sold
                    </h4>
                    <div class="space-y-2">
                        <div
                            v-for="item in sale.items"
                            :key="item.id"
                            class="flex items-center justify-between border-b border-border/30 py-2 last:border-0"
                        >
                            <div class="flex-1">
                                <div class="text-sm font-medium">
                                    {{ item.product_name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ item.quantity }} ×
                                    {{ formatPhilippinePeso(item.unit_price) }}
                                </div>
                            </div>
                            <div class="text-sm font-medium">
                                {{ formatPhilippinePeso(item.total_amount) }}
                            </div>
                        </div>
                        
                        <!-- Total Amount Summary Row -->
                        <div class="flex items-center justify-between border-t-2 border-border pt-3 mt-3">
                            <div class="text-base font-semibold">Total Amount</div>
                            <div class="text-lg font-bold">
                                {{ formatPhilippinePeso(sale.total_amount || 0) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details Section -->
                <div class="space-y-3 rounded-lg bg-muted/20 p-4">
                    <h4
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Payment Details
                    </h4>

                    <!-- Cash Payment Details -->
                    <div v-if="sale.payment_type === 'cash'" class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Payment Method:</span
                            >
                            <span class="font-medium">💵 Cash</span>
                        </div>
                        <div v-if="sale.amount_tendered" class="flex justify-between">
                            <span class="text-muted-foreground">Amount Received:</span>
                            <span class="font-medium">{{ formatPhilippinePeso(sale.amount_tendered) }}</span>
                        </div>
                        <div v-if="sale.payment_type === 'cash' && changeAmountComputed !== null" class="flex justify-between">
                            <span class="text-muted-foreground">Change:</span>
                            <span class="font-medium">{{ formatPhilippinePeso(changeAmountComputed || 0) }}</span>
                        </div>
                        <div v-if="sale.deduct_from_balance && sale.deduct_from_balance > 0" class="flex justify-between">
                            <span class="text-muted-foreground">Used from Balance:</span>
                            <span class="font-medium">{{ formatPhilippinePeso(sale.deduct_from_balance) }}</span>
                        </div>
                    </div>

                    <!-- Utang Payment Details -->
                    <div v-else-if="sale.payment_type === 'utang'" class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Payment Method:</span
                            >
                            <span class="font-medium">📝 Credit (Utang)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Amount Paid:</span>
                            <span class="font-medium">{{ formatPhilippinePeso(sale.paid_amount || 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Balance Summary (only for transactions with customers) -->
                <div v-if="(props.transaction as any)?.previous_balance !== undefined && (props.transaction as any)?.new_balance !== undefined" class="space-y-3 rounded-lg bg-muted/20 p-4">
                    <h4
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Customer Balance
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Previous:</span>
                            <span class="font-medium">{{ formatAmountLocal((props.transaction as any).previous_balance ?? 0) }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-border/50 pt-2 font-semibold"
                        >
                            <span>New Balance:</span>
                            <span class="text-destructive">{{ formatAmountLocal((props.transaction as any).new_balance ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes (if any) -->
                <div v-if="sale?.notes && sale.notes.trim()" class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/20">
                    <h4 class="mb-1 text-sm font-medium text-blue-800 dark:text-blue-200">Notes:</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300">{{ sale.notes }}</p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
