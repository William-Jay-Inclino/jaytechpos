<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
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

const paymentStatus = computed(() => {
    if (!props.transaction) return { label: '', variant: 'secondary' as const };

    if (props.transaction.payment_type === 'cash') {
        return { label: 'Paid in Full', variant: 'default' as const };
    } else {
        return { label: 'Utang', variant: 'destructive' as const };
    }
});

const outstandingBalance = computed(() => {
    if (!props.transaction) return '';
    const totalAmount = props.transaction.total_amount || 0;
    const paidAmount = props.transaction.paid_amount || 0;
    const remaining = totalAmount - paidAmount;
    return remaining > 0 ? formatPhilippinePeso(remaining) : null;
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

            <div v-if="transaction" class="space-y-5">
                <!-- Payment Status & Amount -->
                <div class="space-y-3 rounded-lg bg-muted/30 p-4 text-center">
                    <div>
                        <div class="mb-1 text-xs text-muted-foreground">
                            Total Amount
                        </div>
                        <div class="text-2xl font-bold">
                            {{
                                formatPhilippinePeso(
                                    transaction.total_amount || 0,
                                )
                            }}
                        </div>
                    </div>

                    <Badge :variant="paymentStatus.variant" class="text-xs">{{
                        paymentStatus.label
                    }}</Badge>

                    <!-- Paid Amount for Utang -->
                    <div
                        v-if="transaction.payment_type === 'utang'"
                        class="text-sm"
                    >
                        <span class="text-muted-foreground">Paid Amount: </span>
                        <span class="font-medium">{{
                            formatPhilippinePeso(transaction.paid_amount || 0)
                        }}</span>
                    </div>

                    <!-- Outstanding Balance if Utang -->
                    <div
                        v-if="outstandingBalance"
                        class="text-sm font-medium text-destructive"
                    >
                        Remaining: {{ outstandingBalance }}
                    </div>
                </div>

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
                            class="flex items-center justify-between border-b border-border/50 py-2 last:border-0"
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
                    </div>
                </div>

                <!-- Balance Summary (only for Credit) -->
                <div
                    v-if="transaction.payment_type === 'utang'"
                    class="space-y-2 rounded-lg bg-muted/20 p-3"
                >
                    <h4
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Customer Balance
                    </h4>
                    <div class="flex justify-between text-sm">
                        <span>Previous:</span>
                        <span>{{
                            transaction.formatted_previous_balance
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t pt-2 text-sm font-medium"
                    >
                        <span>New balance:</span>
                        <span class="text-destructive">{{
                            transaction.formatted_new_balance
                        }}</span>
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
