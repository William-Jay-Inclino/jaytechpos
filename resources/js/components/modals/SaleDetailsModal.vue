<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatPhilippinePeso } from '@/utils/timezone';
import { computed } from 'vue';

interface SalesItem {
    id: number;
    product_name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

interface SaleTransaction {
    id: number;
    type: 'sale';
    date: string;
    amount: number;
    formatted_amount: string;
    description: string;
    invoice_number?: string;
    payment_type?: 'cash' | 'utang';
    total_amount?: number;
    paid_amount?: number;
    amount_tendered?: number;
    deduct_from_balance?: number;
    change_amount?: number;
    notes?: string;
    previous_balance?: number;
    new_balance?: number;
    formatted_previous_balance?: string;
    formatted_new_balance?: string;
    sales_items?: SalesItem[];
}

const props = defineProps<{
    open: boolean;
    transaction: SaleTransaction | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const formatDate = computed(() => {
    if (!props.transaction) return '';
    const date = new Date(props.transaction.date);
    return date.toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
            <DialogHeader class="pb-2 text-center">
                <DialogTitle class="text-xl"
                    >Receipt #{{
                        transaction?.invoice_number || 'N/A'
                    }}</DialogTitle
                >
                <DialogDescription class="text-sm text-muted-foreground">
                    {{ formatDate }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="transaction" class="space-y-4">
                <!-- Items Purchased -->
                <div
                    v-if="
                        transaction.sales_items &&
                        transaction.sales_items.length > 0
                    "
                    class="space-y-3"
                >
                    <h4
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Items Sold
                    </h4>
                    <div class="space-y-2">
                        <div
                            v-for="item in transaction.sales_items"
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
                                {{ formatPhilippinePeso(item.total_price) }}
                            </div>
                        </div>
                        
                        <!-- Total Amount Summary Row -->
                        <div class="flex items-center justify-between border-t-2 border-border pt-3 mt-3">
                            <div class="text-base font-semibold">Total Amount</div>
                            <div class="text-lg font-bold">
                                {{ formatPhilippinePeso(transaction.total_amount || 0) }}
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
                    <div
                        v-if="transaction.payment_type === 'cash'"
                        class="space-y-2 text-sm"
                    >
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Payment Method:</span
                            >
                            <span class="font-medium">💵 Cash</span>
                        </div>
                        <div
                            v-if="transaction.amount_tendered"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Amount Received:</span
                            >
                            <span class="font-medium">{{
                                formatPhilippinePeso(transaction.amount_tendered)
                            }}</span>
                        </div>
                        <div
                            v-if="
                                transaction.change_amount !== undefined &&
                                transaction.change_amount !== null
                            "
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Change:</span>
                            <span class="font-medium">{{
                                formatPhilippinePeso(transaction.change_amount || 0)
                            }}</span>
                        </div>
                        <div
                            v-if="
                                transaction.deduct_from_balance &&
                                transaction.deduct_from_balance > 0
                            "
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Used from Balance:</span
                            >
                            <span class="font-medium">{{
                                formatPhilippinePeso(transaction.deduct_from_balance)
                            }}</span>
                        </div>
                    </div>

                    <!-- Utang Payment Details -->
                    <div
                        v-else-if="transaction.payment_type === 'utang'"
                        class="space-y-2 text-sm"
                    >
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Payment Method:</span
                            >
                            <span class="font-medium">📝 Credit (Utang)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Amount Paid:</span>
                            <span class="font-medium">{{
                                formatPhilippinePeso(transaction.paid_amount || 0)
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Balance Summary (only for transactions with customers) -->
                <div
                    v-if="transaction.previous_balance !== undefined && transaction.new_balance !== undefined"
                    class="space-y-3 rounded-lg bg-muted/20 p-4"
                >
                    <h4
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Customer Balance
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Previous:</span>
                            <span class="font-medium">{{
                                transaction.formatted_previous_balance
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-border/50 pt-2 font-semibold"
                        >
                            <span>New Balance:</span>
                            <span class="text-destructive">{{
                                transaction.formatted_new_balance
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes (if any) -->
                <div
                    v-if="transaction.notes && transaction.notes.trim()"
                    class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/20"
                >
                    <h4
                        class="mb-1 text-sm font-medium text-blue-800 dark:text-blue-200"
                    >
                        Notes:
                    </h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        {{ transaction.notes }}
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
