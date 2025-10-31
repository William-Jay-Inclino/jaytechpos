<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { showErrorToast, showSuccessToast } from '@/lib/toast';
import { Customer, CustomerTransaction, type BreadcrumbItem } from '@/types';
import {
    formatManilaDateTime,
    formatPhilippinePeso,
    getCurrentManilaDateTime,
} from '@/utils/timezone';
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

// UI Components
import SaleDetailsModal from '@/components/modals/SaleDetailsModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    customers: Array<Customer>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Utang Payment',
        href: '/utang-payments',
    },
];

// Form state
const selectedCustomerId = ref<string>('');
const paymentAmount = ref<string>('');
const paymentDate = ref<string>(getCurrentManilaDateTime()); // Current Manila date and time
const notes = ref<string>('');

// Transaction history state
const transactions = ref<CustomerTransaction[]>([]);
const loadingTransactions = ref(false);

// Modal state
const showSaleModal = ref(false);
const selectedSaleTransaction = ref<CustomerTransaction | null>(null);

// Computed properties
const selectedCustomer = computed(() => {
    if (!selectedCustomerId.value) return null;
    return (
        props.customers.find(
            (c) => c.id === parseInt(selectedCustomerId.value),
        ) || null
    );
});

const isFormValid = computed(() => {
    return (
        selectedCustomerId.value &&
        paymentAmount.value &&
        parseFloat(paymentAmount.value) > 0 &&
        paymentDate.value
    );
});

// Methods
const fetchCustomerTransactions = async (customerId: number) => {
    if (!customerId) {
        transactions.value = [];
        return;
    }

    loadingTransactions.value = true;
    try {
        // Get CSRF token from the meta tag
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/utang-payments/customer/${customerId}/transactions`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin', // Include cookies for authentication
            },
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        transactions.value = data.transactions || [];
    } catch (error) {
        console.error('Error fetching transactions:', error);
        showErrorToast('Failed to load transaction history');
        transactions.value = [];
    } finally {
        loadingTransactions.value = false;
    }
};

const resetForm = () => {
    selectedCustomerId.value = '';
    paymentAmount.value = '';
    paymentDate.value = getCurrentManilaDateTime();
    notes.value = '';
    transactions.value = [];
};

const handleFormSuccess = () => {
    showSuccessToast('Payment recorded successfully!');
    resetForm();

    // Refresh transaction history if a customer was selected
    if (selectedCustomerId.value) {
        fetchCustomerTransactions(parseInt(selectedCustomerId.value));
    }
};

const handleFormError = () => {
    showErrorToast('Failed to record payment. Please try again.');
};

const formatCurrency = formatPhilippinePeso;
const formatDate = formatManilaDateTime;

const getTransactionTypeColor = (type: string) => {
    switch (type) {
        case 'payment':
            return 'text-green-600 dark:text-green-400';
        case 'tracking':
            return 'text-blue-600 dark:text-blue-400';
        case 'sale':
            return 'text-orange-600 dark:text-orange-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getTransactionTypeLabel = (type: string) => {
    switch (type) {
        case 'payment':
            return 'Utang Payment';
        case 'tracking':
            return 'Monthly Interest';
        case 'sale':
            return 'Sale Transaction';
        default:
            return 'Unknown';
    }
};

const openSaleDetails = (transaction: CustomerTransaction) => {
    if (transaction.type === 'sale') {
        selectedSaleTransaction.value = transaction;
        showSaleModal.value = true;
    }
};

const getFormattedPaidAmount = (transaction: CustomerTransaction) => {
    if (transaction.payment_type === 'cash') {
        return 'Full Payment';
    }
    return formatCurrency(transaction.paid_amount || 0);
};

// Watch for customer selection changes
watch(selectedCustomerId, (newCustomerId) => {
    if (newCustomerId) {
        fetchCustomerTransactions(parseInt(newCustomerId));
    } else {
        transactions.value = [];
    }
});
</script>

<template>
    <Head title="Utang Payment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <!-- Page Header -->
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 flex items-center gap-4">
                    <h1
                        class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white"
                    >
                        💳 Utang Payment
                    </h1>
                </div>

                <!-- Main Layout - 2 Columns on Desktop, 1 Column on Mobile -->
                <div
                    class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start lg:gap-8"
                >
                    <!-- Left Column - Payment Form (4 columns on desktop) -->
                    <div class="flex flex-col space-y-6 lg:col-span-4">
                        <!-- Payment Form Card -->
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h2
                                class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                💰 Record Payment
                            </h2>
                            <p
                                class="mb-6 text-sm text-gray-600 dark:text-gray-400"
                            >
                                Record a new utang payment for a customer
                            </p>
                            <Form
                                action="/utang-payments"
                                method="post"
                                :reset-on-success="true"
                                v-slot="{ errors, processing }"
                                @success="handleFormSuccess"
                                @error="handleFormError"
                                class="space-y-6"
                            >
                                <!-- Customer Selection -->
                                <div class="space-y-3">
                                    <Label
                                        for="customer_id"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Customer
                                        <span class="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        v-model="selectedCustomerId"
                                        name="customer_id"
                                    >
                                        <SelectTrigger class="h-12">
                                            <SelectValue
                                                placeholder="Select a customer"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="customer in customers"
                                                :key="customer.id"
                                                :value="customer.id.toString()"
                                            >
                                                {{ customer.name }}
                                                <span
                                                    v-if="
                                                        customer.mobile_number
                                                    "
                                                    class="ml-2 text-gray-500"
                                                >
                                                    ({{
                                                        customer.mobile_number
                                                    }})
                                                </span>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div
                                        v-if="errors.customer_id"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ errors.customer_id }}
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="space-y-3">
                                    <Label
                                        for="payment_amount"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Payment Amount
                                        <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="payment_amount"
                                        name="payment_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        v-model="paymentAmount"
                                        class="h-12 border-2 text-right text-lg font-semibold focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div
                                        v-if="errors.payment_amount"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ errors.payment_amount }}
                                    </div>
                                </div>

                                <!-- Payment Date -->
                                <div class="space-y-3">
                                    <Label
                                        for="payment_date"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Payment Date & Time
                                        <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="payment_date"
                                        name="payment_date"
                                        type="datetime-local"
                                        v-model="paymentDate"
                                        class="h-12 border-2 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div
                                        v-if="errors.payment_date"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ errors.payment_date }}
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="space-y-3">
                                    <Label
                                        for="notes"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Notes
                                        <span class="text-gray-500"
                                            >(Optional)</span
                                        >
                                    </Label>
                                    <Textarea
                                        id="notes"
                                        name="notes"
                                        placeholder="Optional notes about this payment..."
                                        rows="3"
                                        maxlength="1000"
                                        v-model="notes"
                                        class="border-2 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div
                                        v-if="errors.notes"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ errors.notes }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <Button
                                    type="submit"
                                    :disabled="processing || !isFormValid"
                                    class="h-12 w-full bg-green-600 text-lg font-semibold text-white shadow-lg hover:bg-green-700"
                                >
                                    <span
                                        v-if="processing"
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                                        ></div>
                                        Recording Payment...
                                    </span>
                                    <span
                                        v-else
                                        class="flex items-center gap-2"
                                    >
                                        💰 Record Payment
                                    </span>
                                </Button>
                            </Form>
                        </div>

                        <!-- Customer Information Card -->
                        <div
                            v-if="selectedCustomer"
                            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h2
                                class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                👤 Customer Information
                            </h2>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Mobile Number:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{
                                            selectedCustomer.mobile_number ||
                                            'N/A'
                                        }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Interest Rate:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{
                                            selectedCustomer.effective_interest_rate?.toFixed(
                                                2,
                                            ) || '0.00'
                                        }}%</span
                                    >
                                </div>
                                <div
                                    class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20"
                                >
                                    <span
                                        class="text-sm font-medium text-red-800 dark:text-red-200"
                                        >Current Balance:</span
                                    >
                                    <span
                                        class="text-xl font-bold text-red-900 dark:text-red-100"
                                    >
                                        {{
                                            formatCurrency(
                                                selectedCustomer.running_utang_balance ||
                                                    0,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Transaction History (8 columns on desktop) -->
                    <div class="flex h-full flex-col lg:col-span-8">
                        <div
                            class="flex flex-1 flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h2
                                class="mb-3 text-base font-semibold text-gray-900 sm:mb-4 sm:text-lg dark:text-white"
                            >
                                📋 Transaction History
                            </h2>
                            <p
                                class="mb-4 text-xs text-gray-600 sm:mb-6 sm:text-sm dark:text-gray-400"
                            >
                                <span v-if="selectedCustomer">
                                    Recent transactions for
                                    <span class="font-semibold">{{
                                        selectedCustomer.name
                                    }}</span>
                                </span>
                                <span v-else>
                                    Select a customer to view transaction
                                    history
                                </span>
                            </p>
                            <!-- Loading State -->
                            <div
                                v-if="loadingTransactions"
                                class="flex flex-col items-center justify-center space-y-3 py-8 sm:py-12"
                            >
                                <div
                                    class="h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent sm:h-8 sm:w-8"
                                ></div>
                                <div
                                    class="text-xs text-gray-500 sm:text-sm dark:text-gray-400"
                                >
                                    Loading transactions...
                                </div>
                            </div>

                            <!-- No Customer Selected -->
                            <div
                                v-else-if="!selectedCustomer"
                                class="space-y-3 py-8 text-center sm:py-12"
                            >
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 sm:h-16 sm:w-16 dark:bg-gray-700"
                                >
                                    <span class="text-xl sm:text-2xl">👤</span>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-600 sm:text-base dark:text-gray-400"
                                    >
                                        No customer selected
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 sm:text-sm dark:text-gray-500"
                                    >
                                        Choose a customer to view their
                                        transaction history
                                    </p>
                                </div>
                            </div>

                            <!-- No Transactions -->
                            <div
                                v-else-if="transactions.length === 0"
                                class="space-y-3 py-8 text-center sm:py-12"
                            >
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 sm:h-16 sm:w-16 dark:bg-gray-700"
                                >
                                    <span class="text-xl sm:text-2xl">📄</span>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-600 sm:text-base dark:text-gray-400"
                                    >
                                        No transactions found
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 sm:text-sm dark:text-gray-500"
                                    >
                                        This customer has no transaction history
                                        yet
                                    </p>
                                </div>
                            </div>

                            <!-- Transaction List -->
                            <div
                                v-else
                                class="flex-1 space-y-3 overflow-y-auto sm:space-y-4"
                            >
                                <div
                                    v-for="transaction in transactions"
                                    :key="`${transaction.type}-${transaction.id}`"
                                    class="rounded-lg border border-gray-200 bg-gray-50 p-3 transition-colors hover:bg-gray-100 sm:p-4 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                                >
                                    <div
                                        class="flex flex-col space-y-3 sm:flex-row sm:items-start sm:justify-between sm:space-y-0"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3"
                                            >
                                                <span
                                                    :class="[
                                                        'w-fit rounded-full px-2 py-1 text-xs font-semibold sm:px-3',
                                                        transaction.type ===
                                                        'payment'
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                                            : transaction.type ===
                                                                'tracking'
                                                              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
                                                              : 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
                                                    ]"
                                                >
                                                    {{
                                                        getTransactionTypeLabel(
                                                            transaction.type,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    class="text-xs font-medium text-gray-500 dark:text-gray-400"
                                                >
                                                    {{
                                                        formatDate(
                                                            transaction.date,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <p
                                                class="mb-3 text-xs font-semibold break-words text-gray-900 sm:text-sm dark:text-white"
                                            >
                                                {{ transaction.description }}
                                            </p>

                                            <!-- Sale-specific fields -->
                                            <div
                                                v-if="
                                                    transaction.type === 'sale'
                                                "
                                                class="mb-3 space-y-2"
                                            >
                                                <div
                                                    class="flex flex-col gap-2 text-xs sm:flex-row sm:items-center sm:gap-4 sm:text-sm"
                                                >
                                                    <span
                                                        class="text-gray-600 dark:text-gray-400"
                                                    >
                                                        Total:
                                                        <span
                                                            class="font-semibold text-gray-900 dark:text-white"
                                                            >{{
                                                                formatCurrency(
                                                                    transaction.total_amount ||
                                                                        0,
                                                                )
                                                            }}</span
                                                        >
                                                    </span>
                                                    <span
                                                        class="text-gray-600 dark:text-gray-400"
                                                    >
                                                        Paid:
                                                        <span
                                                            class="font-semibold text-gray-900 dark:text-white"
                                                            >{{
                                                                getFormattedPaidAmount(
                                                                    transaction,
                                                                )
                                                            }}</span
                                                        >
                                                    </span>
                                                    <Badge
                                                        :variant="
                                                            transaction.payment_type ===
                                                            'cash'
                                                                ? 'default'
                                                                : 'destructive'
                                                        "
                                                        class="w-fit text-xs font-medium"
                                                    >
                                                        {{
                                                            transaction.payment_type?.toUpperCase()
                                                        }}
                                                    </Badge>
                                                </div>
                                                <div
                                                    v-if="transaction.notes"
                                                    class="rounded bg-gray-100 p-2 text-xs text-gray-600 dark:bg-gray-600 dark:text-gray-400"
                                                >
                                                    <span class="font-medium"
                                                        >Notes:</span
                                                    >
                                                    {{ transaction.notes }}
                                                </div>
                                            </div>

                                            <!-- Balance changes -->
                                            <div
                                                v-if="
                                                    (transaction.type ===
                                                        'payment' ||
                                                        transaction.type ===
                                                            'sale' ||
                                                        transaction.type ===
                                                            'tracking') &&
                                                    transaction.previous_balance !==
                                                        undefined
                                                "
                                                class="rounded bg-gray-100 p-2 text-xs text-gray-600 dark:bg-gray-600 dark:text-gray-400"
                                            >
                                                <span
                                                    v-if="
                                                        transaction.type ===
                                                        'tracking'
                                                    "
                                                    class="font-medium break-words"
                                                >
                                                    Previous Month:
                                                    {{
                                                        transaction.formatted_previous_balance
                                                    }}
                                                    → Current Month:
                                                    {{
                                                        transaction.formatted_new_balance
                                                    }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="font-medium break-words"
                                                >
                                                    Balance:
                                                    {{
                                                        transaction.formatted_previous_balance
                                                    }}
                                                    →
                                                    {{
                                                        transaction.formatted_new_balance
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex flex-col items-start gap-2 sm:ml-4 sm:flex-row sm:items-center sm:gap-3"
                                        >
                                            <!-- View Details Button for Sales -->
                                            <Button
                                                v-if="
                                                    transaction.type === 'sale'
                                                "
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    openSaleDetails(transaction)
                                                "
                                                class="w-fit text-xs text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                            >
                                                View Details
                                            </Button>

                                            <!-- Amount -->
                                            <span
                                                class="w-fit rounded-lg bg-green-50 px-2 py-1 text-sm font-bold text-green-600 sm:px-3 sm:text-lg dark:bg-green-900/20 dark:text-green-400"
                                            >
                                                {{
                                                    transaction.formatted_amount
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sale Details Modal -->
        <SaleDetailsModal
            v-model:open="showSaleModal"
            :transaction="selectedSaleTransaction as any"
        />
    </AppLayout>
</template>
