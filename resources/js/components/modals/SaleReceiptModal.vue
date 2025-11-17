<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    formatManilaDateTime,
    getCurrentManilaDateTime,
} from '@/utils/timezone';

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
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="max-h-[85vh] max-w-lg flex flex-col">
            <DialogHeader class="pb-2 text-center flex-shrink-0">
                <DialogTitle
                    class="flex items-center justify-center gap-2 text-xl text-green-600 dark:text-green-400"
                >
                    <span class="text-2xl">✅</span>
                    Sale Completed!
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Transaction processed successfully
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 space-y-5 overflow-y-auto min-h-0">
                <!-- Receipt Header -->
                <div
                    class="space-y-2 rounded-lg bg-muted/30 p-4 text-center"
                >
                    <h3 class="text-lg font-bold">SimplePOS</h3>
                    <p class="text-sm text-muted-foreground">
                        Point of Sale Receipt
                    </p>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-content" class="space-y-4">
                    <!-- Receipt Details -->
                    <div v-if="saleData" class="space-y-4">
                        <!-- Basic Info -->
                        <div
                            class="space-y-2 rounded-lg bg-muted/20 p-3 text-sm"
                        >
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Invoice #:</span
                                >
                                <span class="font-mono font-medium">{{
                                    saleData.invoice_number
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Date:</span
                                >
                                <span class="font-medium">{{
                                    formatManilaDateTime(
                                        saleData.transaction_date,
                                    )
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Customer:</span
                                >
                                <span class="font-medium">{{
                                    saleData.customer_name ||
                                    'Walk-in Customer'
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Cashier:</span
                                >
                                <span class="font-medium">{{
                                    saleData.cashier
                                }}</span>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="space-y-3">
                            <h4
                                class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Items Sold
                            </h4>
                            <div class="space-y-2">
                                <div
                                    v-for="item in saleData.items"
                                    :key="item.id"
                                    class="flex items-center justify-between border-b border-border/50 py-2 last:border-0"
                                >
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">
                                            {{ item.product_name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.quantity }} × ₱{{
                                                item.unit_price.toFixed(2)
                                            }}
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium">
                                        ₱{{ item.total_amount.toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div
                            class="space-y-3 rounded-lg bg-muted/30 p-4 text-center"
                        >
                            <div class="mb-1 text-xs text-muted-foreground">
                                Total Amount
                            </div>
                            <div class="text-2xl font-bold">
                                ₱{{ saleData.total_amount.toFixed(2) }}
                            </div>
                        </div>

                        <!-- Payment Details Section -->
                        <div class="space-y-2 rounded-lg bg-muted/20 p-3">
                            <h4
                                class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Payment Details
                            </h4>

                            <!-- Cash Payment Details -->
                            <div
                                v-if="saleData.payment_type === 'cash'"
                                class="space-y-1 text-sm"
                            >
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Payment Method:</span
                                    >
                                    <span class="font-medium">💵 Cash</span>
                                </div>
                                <div
                                    v-if="saleData.amount_tendered"
                                    class="flex justify-between"
                                >
                                    <span class="text-muted-foreground"
                                        >Amount Received:</span
                                    >
                                    <span
                                        >₱{{
                                            saleData.amount_tendered.toFixed(
                                                2,
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="
                                        saleData.change_amount !==
                                            undefined &&
                                        saleData.change_amount !== null
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="text-muted-foreground"
                                        >Change:</span
                                    >
                                    <span
                                        >₱{{
                                            (
                                                saleData.change_amount || 0
                                            ).toFixed(2)
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="
                                        saleData.balance_payment &&
                                        saleData.balance_payment > 0
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="text-muted-foreground"
                                        >Payment Towards Balance:</span
                                    >
                                    <span
                                        >₱{{
                                            (
                                                saleData.balance_payment ||
                                                0
                                            ).toFixed(2)
                                        }}</span
                                    >
                                </div>
                            </div>

                            <!-- Utang Payment Details -->
                            <div
                                v-else-if="
                                    saleData.payment_type === 'utang'
                                "
                                class="space-y-1 text-sm"
                            >
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Payment Method:</span
                                    >
                                    <span class="font-medium"
                                        >📝 Credit (Utang)</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Amount Paid:</span
                                    >
                                    <span
                                        >₱{{
                                            saleData.paid_amount.toFixed(2)
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Customer Balance (only if customer involved) -->
                        <div
                            v-if="saleData.customer_id"
                            class="space-y-2 rounded-lg bg-muted/20 p-3"
                        >
                            <h4
                                class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Customer Balance
                            </h4>
                            <div class="space-y-1 text-sm">
                                <div
                                    v-if="
                                        saleData.original_customer_balance !==
                                        undefined
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="text-muted-foreground"
                                        >Previous:</span
                                    >
                                    <span
                                        >₱{{
                                            (
                                                saleData.original_customer_balance ||
                                                0
                                            ).toFixed(2)
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="flex justify-between border-t pt-1 font-medium"
                                >
                                    <span>New Balance:</span>
                                    <span
                                        :class="
                                            (saleData.new_customer_balance ||
                                                0) > 0
                                                ? 'text-destructive'
                                                : ''
                                        "
                                    >
                                        ₱{{
                                            (
                                                saleData.new_customer_balance ||
                                                0
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Message -->
                        <div class="rounded-lg bg-muted/30 p-3 text-center">
                            <p class="text-sm font-medium">
                                Thank you for your business!
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    formatManilaDateTime(
                                        getCurrentManilaDateTime(),
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex justify-center border-t pt-4 flex-shrink-0">
                <Button
                    @click="handleClose"
                    class="bg-green-600 px-8 text-white hover:bg-green-700"
                >
                    Close & New Sale
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt-content,
    #receipt-content * {
        visibility: visible;
    }
    #receipt-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>