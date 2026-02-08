<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { showErrorToast, showSuccessToast, showInfoToast } from '@/lib/toast';
import { Customer, CustomerTransaction, type BreadcrumbItem } from '@/types';
import {
    getCurrentManilaDateTime,
} from '@/utils/timezone';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { Search, UserPlus, X } from 'lucide-vue-next';

// UI Components
import CustomerTransactionHistory from '@/components/CustomerTransactionHistory.vue';
import { Button } from '@/components/ui/button';
import { Input, InputCurrency } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/utils/currency';

const props = defineProps<{
    customers: Array<Customer>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Utang',
        href: '/utang-payments',
    },
];

// Form state
const selectedCustomerId = ref<string>('');
const paymentAmount = ref<string | number>('');
const paymentDate = ref<string>(getCurrentManilaDateTime());
const notes = ref<string>('');
const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Customer search state
const customerSearch = ref('');
const showCustomerDropdown = ref(false);

// Customer balance loading state
const isLoadingBalance = ref(false);
const customerBalance = ref<number>(0);

// Transaction history state
const transactions = ref<CustomerTransaction[]>([]);
const loadingTransactions = ref(false);
const showTransactionHistory = ref(false);

// Computed properties
const selectedCustomer = computed(() => {
    if (!selectedCustomerId.value) return null;
    const customer = props.customers.find(
        (c) => c.id === parseInt(selectedCustomerId.value),
    );
    if (!customer) return null;
    return {
        ...customer,
        running_utang_balance: customerBalance.value,
    };
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return props.customers;
    return props.customers.filter(customer =>
        customer.name.toLowerCase().includes(customerSearch.value.toLowerCase())
        // (customer.mobile_number && customer.mobile_number.toLowerCase().includes(customerSearch.value.toLowerCase()))
    );
});

const selectedCustomerName = computed(() => {
    const customer = props.customers.find(c => c.id.toString() === selectedCustomerId.value);
    return customer ? `${customer.name}${customer.mobile_number ? ` (${customer.mobile_number})` : ''}` : '';
});

const paymentAmountError = computed(() => {
    if (!paymentAmount.value || !selectedCustomer.value) return '';

    const amount = typeof paymentAmount.value === 'number' ? paymentAmount.value : parseFloat(String(paymentAmount.value));
    const balance = selectedCustomer.value.running_utang_balance || 0;

    if (Number.isNaN(amount) || amount <= 0) {
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
        (typeof paymentAmount.value === 'number' ? paymentAmount.value > 0 : parseFloat(String(paymentAmount.value)) > 0) &&
        paymentDate.value &&
        !paymentAmountError.value &&
        !isLoadingBalance.value &&
        !processing.value
    );
});

// Methods
const fetchCustomerBalance = async (customerId: number): Promise<void> => {
    if (!customerId) {
        customerBalance.value = 0;
        return;
    }

    isLoadingBalance.value = true;
    try {
        const response = await axios.get(`/api/customers/${customerId}/balance`);
        customerBalance.value = response.data.balance;
    } catch (error) {
        console.error('Error fetching customer balance:', error);
        customerBalance.value = 0;
    } finally {
        isLoadingBalance.value = false;
    }
};

const fetchCustomerTransactions = async (customerId: number) => {
    if (!customerId) {
        transactions.value = [];
        return;
    }

    loadingTransactions.value = true;
    try {
        const response = await axios.get(`/api/customers/${customerId}/transactions`, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            withCredentials: true,
        });
        transactions.value = response.data.transactions || [];
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
    
    // Fetch up-to-date balance when customer is selected
    if (customerId) {
        fetchCustomerBalance(parseInt(customerId));
    } else {
        customerBalance.value = 0;
    }
}

const resetForm = () => {
    selectedCustomerId.value = '';
    paymentAmount.value = '';
    paymentDate.value = getCurrentManilaDateTime();
    notes.value = '';
    errors.value = {};
    customerSearch.value = '';
    showCustomerDropdown.value = false;
    customerBalance.value = 0;
    transactions.value = [];
    showTransactionHistory.value = false;
};

const handleFormSuccess = () => {
    showSuccessToast('Payment recorded successfully!');
    resetForm();
};

const showTransactionHistoryView = async () => {
    if (selectedCustomerId.value) {
        // Show the history view and inform the user
        showTransactionHistory.value = true;
        showInfoToast('Showing transaction history');

        // Wait for DOM to update so the component shell exists
        await nextTick();

        // Fetch transactions and wait for the data to be set so the
        // component can render its final height.
        await fetchCustomerTransactions(parseInt(selectedCustomerId.value));

        // Wait again for DOM to reflect the loaded transactions
        await nextTick();

        // If on a mobile viewport, scroll smoothly to the transaction history
        // using the component's root element id
        try {
            if (window.innerWidth < 1024) {
                const el = document.getElementById('customer-transaction-history');
                if (el) {
                    // Align the bottom of the element into view so the latest
                    // entries (usually at the bottom) are visible on mobile.
                    el.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }
            }
        } catch (e) {
            // ignore any scrolling errors
            console.warn('Scrolling to transaction history failed', e);
        }
    }
};

async function handleSubmit(): Promise<void> {
    if (processing.value) return;

    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/utang-payments', {
            customer_id: selectedCustomerId.value,
            payment_amount: typeof paymentAmount.value === 'number' ? paymentAmount.value : parseFloat(String(paymentAmount.value)),
            payment_date: paymentDate.value,
            notes: notes.value,
        });

        handleFormSuccess();
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        showErrorToast('Failed to record payment. Please try again.');
    } finally {
        processing.value = false;
    }
}

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
        // Fetch balance when customer changes
        fetchCustomerBalance(parseInt(newCustomerId));
        
        // Only fetch transactions if transaction history is being shown
        if (showTransactionHistory.value) {
            fetchCustomerTransactions(parseInt(newCustomerId));
        }
    } else {
        transactions.value = [];
        customerBalance.value = 0;
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
                            data-testid="payment-form-card"
                            class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                        >
                            <form @submit.prevent="handleSubmit" class="space-y-6" data-testid="payment-form">
                                <!-- Customer Selection -->
                                <div class="space-y-3">
                                    <label
                                        for="customer_id"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Customer
                                    </label>
                                    <div class="relative customer-dropdown">
                                        <input
                                            type="hidden"
                                            name="customer_id"
                                            :value="selectedCustomerId"
                                        />
                                        <div
                                            @click="showCustomerDropdown = !showCustomerDropdown"
                                            data-testid="customer-dropdown-trigger"
                                            class="flex h-12 w-full cursor-pointer items-center justify-between rounded-md border-2 border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            <span class="truncate text-left" data-testid="selected-customer-name">
                                                {{ selectedCustomerName || '---' }}
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    v-if="selectedCustomerId"
                                                    @click.stop="selectCustomer('')"
                                                    data-testid="clear-customer-btn"
                                                    class="h-4 w-4 rounded hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center transition-colors"
                                                    title="Clear customer"
                                                >
                                                    <X class="h-3 w-3" />
                                                </button>
                                                <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m6 9 6 6 6-6"/>
                                                </svg>
                                            </div>
                                        </div>
                                        
                                        <div
                                            v-if="showCustomerDropdown"
                                                data-testid="customer-dropdown-list"
                                                class="absolute z-50 mt-1 max-h-[60vh] w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg dark:border-gray-700"
                                        >
                                            <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                                <input
                                                    v-model="customerSearch"
                                                    placeholder="Enter name of customer..."
                                                    data-testid="customer-search-input"
                                                    class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                                                    @click.stop
                                                />
                                            </div>
                                            <div class="max-h-[52vh] overflow-auto">
                                                <div
                                                    v-for="customer in filteredCustomers"
                                                    :key="customer.id"
                                                    @click="selectCustomer(customer.id.toString())"
                                                    :data-testid="`customer-option-${customer.id}`"
                                                    class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                                >
                                                    <div class="flex flex-col">
                                                        <div class="font-medium">{{ customer.name }}</div>
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
                                                    <p>
                                                        No customer found.
                                                    </p>
                                                    <button
                                                        type="button"
                                                        @click="router.visit('/customers/create')"
                                                        class="inline-flex items-center gap-2 rounded-md bg-primary text-primary-foreground px-3 py-1.5 text-xs font-medium shadow-sm hover:opacity-90 transition btn-interactive"
                                                    >
                                                        <UserPlus class="h-4 w-4" />
                                                        Add Customer
                                                    </button>
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

                                    <!-- Customer Balance Display -->
                                    <div v-if="selectedCustomer">
                                        <div
                                            data-testid="balance-display"
                                            class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20 mb-2"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-amber-800 dark:text-amber-200"
                                                    >Outstanding Balance:</span
                                                >
                                                <span v-if="isLoadingBalance" data-testid="balance-loading" class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                                                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-amber-600 border-t-transparent dark:border-amber-400"></div>
                                                    <span class="text-sm">Loading...</span>
                                                </span>
                                                <span v-else data-testid="balance-value" class="text-lg font-bold text-amber-900 dark:text-amber-100"
                                                    >{{ formatCurrency(selectedCustomer.running_utang_balance) }}</span
                                                >
                                            </div>
                                        </div>
                                        
                                        <!-- Interest Rate and View Transaction History Button -->
                                        <div class="flex items-center justify-between">
                                            <span data-testid="interest-rate" class="text-xs text-gray-500 dark:text-gray-400">
                                                Interest Rate: {{ selectedCustomer.interest_rate?.toFixed(2) || '0.00' }}%
                                            </span>
                                            <Button
                                                type="button"
                                                @click="showTransactionHistoryView"
                                                variant="ghost"
                                                size="sm"
                                                data-testid="view-history-btn"
                                                class="h-7 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                📋 View History
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label
                                            for="payment-amount"
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Payment Amount
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <InputCurrency
                                            id="payment_amount"
                                            name="payment_amount"
                                            v-model="paymentAmount"
                                            step="0.01"
                                            min="0.01"
                                            :max="selectedCustomer?.running_utang_balance || undefined"
                                            class="h-12 border-2 text-right text-lg font-semibold focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div
                                        v-if="paymentAmountError"
                                        data-testid="payment-amount-error"
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
                                        data-testid="remaining-balance"
                                        class="text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        Remaining balance: {{ formatCurrency((selectedCustomer.running_utang_balance || 0) - parseFloat(String(paymentAmount || '0'))) }}
                                    </div>
                                </div>

                                <!-- Payment Date -->
                                <div class="space-y-3">
                                    <label
                                        for="payment-date"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Payment Date & Time
                                    </label>
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
                                    <label
                                        for="notes"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Notes  <span class="text-gray-400 dark:text-gray-400">(Optional)</span>
                                    </label>
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
                                    :disabled="!isFormValid"
                                    data-testid="submit-payment-btn"
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
                            </form>
                        </div>
                    </div>

                    <!-- Right Column - Transaction History (8 columns on desktop) -->
                    <div class="flex h-full flex-col lg:col-span-8">
                        <CustomerTransactionHistory
                            id="customer-transaction-history"
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
