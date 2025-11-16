<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { showErrorToast, showSuccessToast } from '@/lib/toast';
import { Customer, CustomerTransaction, type BreadcrumbItem } from '@/types';
import {
    formatPhilippinePeso,
    getCurrentManilaDateTime,
} from '@/utils/timezone';
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Search } from 'lucide-vue-next';

// UI Components
import CustomerTransactionHistory from '@/components/CustomerTransactionHistory.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

// Customer search state
const customerSearch = ref('');
const showCustomerDropdown = ref(false);

// Transaction history state
const transactions = ref<CustomerTransaction[]>([]);
const loadingTransactions = ref(false);
const showTransactionHistory = ref(false);

// Computed properties
const selectedCustomer = computed(() => {
    if (!selectedCustomerId.value) return null;
    return (
        props.customers.find(
            (c) => c.id === parseInt(selectedCustomerId.value),
        ) || null
    );
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return props.customers;
    return props.customers.filter(customer =>
        customer.name.toLowerCase().includes(customerSearch.value.toLowerCase()) ||
        (customer.mobile_number && customer.mobile_number.toLowerCase().includes(customerSearch.value.toLowerCase()))
    );
});

const selectedCustomerName = computed(() => {
    const customer = props.customers.find(c => c.id.toString() === selectedCustomerId.value);
    return customer ? `${customer.name}${customer.mobile_number ? ` (${customer.mobile_number})` : ''}` : '';
});

const paymentAmountError = computed(() => {
    if (!paymentAmount.value || !selectedCustomer.value) return '';
    
    const amount = parseFloat(paymentAmount.value);
    const balance = selectedCustomer.value.running_utang_balance || 0;
    
    if (amount <= 0) {
        return 'Payment amount must be greater than zero';
    }
    
    if (amount > balance) {
        return `Payment amount cannot exceed current balance of ${formatCurrency(balance)}`;
    }
    
    return '';
});

const isFormValid = computed(() => {
    return (
        selectedCustomerId.value &&
        paymentAmount.value &&
        parseFloat(paymentAmount.value) > 0 &&
        paymentDate.value &&
        !paymentAmountError.value
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
            `/api/customers/${customerId}/transactions`,
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

// Functions to handle selection
function selectCustomer(customerId: string) {
    selectedCustomerId.value = customerId;
    showCustomerDropdown.value = false;
    customerSearch.value = '';
    showTransactionHistory.value = false; // Hide transaction history when customer changes
}

const resetForm = () => {
    selectedCustomerId.value = '';
    paymentAmount.value = '';
    paymentDate.value = getCurrentManilaDateTime();
    notes.value = '';
    customerSearch.value = '';
    showCustomerDropdown.value = false;
    transactions.value = [];
    showTransactionHistory.value = false;
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

const showTransactionHistoryView = () => {
    if (selectedCustomerId.value) {
        showTransactionHistory.value = true;
        fetchCustomerTransactions(parseInt(selectedCustomerId.value));
    }
};

const formatCurrency = formatPhilippinePeso;

// Handle click outside to close dropdowns
function handleClickOutside(event: MouseEvent) {
    const customerDropdown = document.querySelector('.customer-dropdown');
    if (customerDropdown && !customerDropdown.contains(event.target as Node)) {
        showCustomerDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Watch for customer selection changes
watch(selectedCustomerId, (newCustomerId, oldCustomerId) => {
    // Hide transaction history immediately when selection starts to change
    if (newCustomerId !== oldCustomerId) {
        showTransactionHistory.value = false;
    }
    
    if (newCustomerId) {
        // Only fetch if transaction history is being shown
        if (showTransactionHistory.value) {
            fetchCustomerTransactions(parseInt(newCustomerId));
        }
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

                <!-- Main Layout - 2 Columns on Desktop, 1 Column on Mobile -->
                <div
                    class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start lg:gap-8"
                >
                    <!-- Left Column - Payment Form (4 columns on desktop) -->
                    <div class="flex flex-col space-y-6 lg:col-span-4">
                        <!-- Payment Form Card -->
                        <div
                            class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                        >
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
                                    <div class="relative customer-dropdown">
                                        <input
                                            type="hidden"
                                            name="customer_id"
                                            :value="selectedCustomerId"
                                        />
                                        <div
                                            @click="showCustomerDropdown = !showCustomerDropdown"
                                            class="flex h-12 w-full cursor-pointer items-center justify-between rounded-md border-2 border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            <span class="truncate text-left">
                                                {{ selectedCustomerName || '---' }}
                                            </span>
                                            <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m6 9 6 6 6-6"/>
                                            </svg>
                                        </div>
                                        
                                        <div
                                            v-if="showCustomerDropdown"
                                            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                                <input
                                                    v-model="customerSearch"
                                                    placeholder="Search customers by name or mobile number..."
                                                    class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                                                    @click.stop
                                                />
                                            </div>
                                            <div class="max-h-40 overflow-auto">
                                                <div
                                                    v-for="customer in filteredCustomers"
                                                    :key="customer.id"
                                                    @click="selectCustomer(customer.id.toString())"
                                                    class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                                >
                                                    <div class="flex flex-col">
                                                        <div class="font-medium">{{ customer.name }}</div>
                                                        <div 
                                                            v-if="customer.mobile_number"
                                                            class="text-xs text-gray-500 dark:text-gray-400"
                                                        >
                                                            {{ customer.mobile_number }}
                                                        </div>
                                                        <div 
                                                            v-if="customer.running_utang_balance"
                                                            class="text-xs text-red-600 dark:text-red-400 font-medium"
                                                        >
                                                            Balance: {{ formatCurrency(customer.running_utang_balance) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="filteredCustomers.length === 0"
                                                    class="py-6 text-center text-sm text-muted-foreground"
                                                >
                                                    No customers found.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-if="errors.customer_id"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ errors.customer_id }}
                                    </div>

                                    <!-- Customer Info Below Selection -->
                                    <div v-if="selectedCustomer" class="space-y-2">
                                        <!-- Interest Rate and View Transaction History Button -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Interest Rate: {{ selectedCustomer.effective_interest_rate?.toFixed(2) || '0.00' }}%
                                            </span>
                                            <Button
                                                type="button"
                                                @click="showTransactionHistoryView"
                                                variant="ghost"
                                                size="sm"
                                                class="h-7 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                📋 View History
                                            </Button>
                                        </div>
                                        
                                        <!-- Current Balance (Highlighted) -->
                                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-red-800 dark:text-red-200">
                                                    Current Balance:
                                                </span>
                                                <span class="text-xl font-bold text-red-900 dark:text-red-100">
                                                    {{ formatCurrency(selectedCustomer.running_utang_balance || 0) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <Label
                                            for="payment_amount"
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Payment Amount
                                            <span class="text-red-500">*</span>
                                        </Label>
                                    </div>
                                    <div class="relative">
                                        <Input
                                            id="payment_amount"
                                            name="payment_amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            :max="selectedCustomer?.running_utang_balance || undefined"
                                            placeholder="0.00"
                                            v-model="paymentAmount"
                                            class="h-12 border-2 text-right text-lg font-semibold focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div
                                        v-if="paymentAmountError"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ paymentAmountError }}
                                    </div>
                                    <div
                                        v-else-if="errors.payment_amount"
                                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                    >
                                        {{ errors.payment_amount }}
                                    </div>
                                    <div
                                        v-if="selectedCustomer && !paymentAmountError && paymentAmount"
                                        class="text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        Remaining balance: {{ formatCurrency((selectedCustomer.running_utang_balance || 0) - parseFloat(paymentAmount || '0')) }}
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
                                    </Label>
                                    <Textarea
                                        id="notes"
                                        name="notes"
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
                    </div>

                    <!-- Right Column - Transaction History (8 columns on desktop) -->
                    <div class="flex h-full flex-col lg:col-span-8">
                        <CustomerTransactionHistory
                            v-if="showTransactionHistory"
                            :transactions="transactions"
                            :loading="loadingTransactions"
                            :customer-name="selectedCustomer?.name"
                            :customer-id="selectedCustomer?.id"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
