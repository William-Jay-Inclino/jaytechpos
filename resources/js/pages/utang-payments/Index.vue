<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Customer, CustomerTransaction, type BreadcrumbItem } from '@/types';
import { Head, Form, router } from '@inertiajs/vue3';
import { getCurrentManilaDateTime, formatManilaDateTime, formatPhilippinePeso } from '@/utils/timezone';
import { showSuccessToast, showErrorToast } from '@/lib/toast';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import SaleDetailsModal from '@/components/modals/SaleDetailsModal.vue';

const props = defineProps<{
    customers: Array<Customer>,
}>()

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
    return props.customers.find(c => c.id === parseInt(selectedCustomerId.value)) || null;
});

const isFormValid = computed(() => {
    return selectedCustomerId.value && 
           paymentAmount.value && 
           parseFloat(paymentAmount.value) > 0 && 
           paymentDate.value;
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
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/utang-payments/customer/${customerId}/transactions`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin' // Include cookies for authentication
        });
        
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
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center gap-4 mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">💳 Utang Payment</h1>
                </div>

                <!-- Main Layout - 2 Columns on Desktop, 1 Column on Mobile -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 lg:items-start">
                    
                    <!-- Left Column - Payment Form (4 columns on desktop) -->
                    <div class="lg:col-span-4 space-y-6 flex flex-col">
                        <!-- Payment Form Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💰 Record Payment</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Record a new utang payment for a customer</p>
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
                                    <Label for="customer_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Customer <span class="text-red-500">*</span>
                                    </Label>
                                    <Select v-model="selectedCustomerId" name="customer_id">
                                        <SelectTrigger class="h-12">
                                            <SelectValue placeholder="Select a customer" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem 
                                                v-for="customer in customers" 
                                                :key="customer.id" 
                                                :value="customer.id.toString()"
                                            >
                                                {{ customer.name }}
                                                <span v-if="customer.mobile_number" class="text-gray-500 ml-2">
                                                    ({{ customer.mobile_number }})
                                                </span>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="errors.customer_id" class="text-sm text-red-600 dark:text-red-400 px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        {{ errors.customer_id }}
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="space-y-3">
                                    <Label for="payment_amount" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Payment Amount <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="payment_amount"
                                        name="payment_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        v-model="paymentAmount"
                                        class="h-12 text-right text-lg font-semibold border-2 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div v-if="errors.payment_amount" class="text-sm text-red-600 dark:text-red-400 px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        {{ errors.payment_amount }}
                                    </div>
                                </div>

                                <!-- Payment Date -->
                                <div class="space-y-3">
                                    <Label for="payment_date" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Payment Date & Time <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="payment_date"
                                        name="payment_date"
                                        type="datetime-local"
                                        v-model="paymentDate"
                                        class="h-12 border-2 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div v-if="errors.payment_date" class="text-sm text-red-600 dark:text-red-400 px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        {{ errors.payment_date }}
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="space-y-3">
                                    <Label for="notes" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Notes <span class="text-gray-500">(Optional)</span>
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
                                    <div v-if="errors.notes" class="text-sm text-red-600 dark:text-red-400 px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        {{ errors.notes }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <Button 
                                    type="submit" 
                                    :disabled="processing || !isFormValid"
                                    class="w-full h-12 text-lg font-semibold bg-green-600 hover:bg-green-700 text-white shadow-lg"
                                >
                                    <span v-if="processing" class="flex items-center gap-2">
                                        <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                        Recording Payment...
                                    </span>
                                    <span v-else class="flex items-center gap-2">
                                        💰 Record Payment
                                    </span>
                                </Button>
                            </Form>
                        </div>

                        <!-- Customer Information Card -->
                        <div v-if="selectedCustomer" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👤 Customer Information</h2>
                            
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mobile Number:</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedCustomer.mobile_number || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Interest Rate:</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedCustomer.effective_interest_rate?.toFixed(2) || '0.00' }}%</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                    <span class="text-sm font-medium text-red-800 dark:text-red-200">Current Balance:</span>
                                    <span class="text-xl font-bold text-red-900 dark:text-red-100">
                                        {{ formatCurrency(selectedCustomer.running_utang_balance || 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Transaction History (8 columns on desktop) -->
                    <div class="lg:col-span-8 flex flex-col h-full">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6 flex-1 flex flex-col">
                            <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4">📋 Transaction History</h2>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-4 sm:mb-6">
                                <span v-if="selectedCustomer">
                                    Recent transactions for <span class="font-semibold">{{ selectedCustomer.name }}</span>
                                </span>
                                <span v-else>
                                    Select a customer to view transaction history
                                </span>
                            </p>
                            <!-- Loading State -->
                            <div v-if="loadingTransactions" class="flex flex-col items-center justify-center py-8 sm:py-12 space-y-3">
                                <div class="w-6 h-6 sm:w-8 sm:h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Loading transactions...</div>
                            </div>

                            <!-- No Customer Selected -->
                            <div v-else-if="!selectedCustomer" class="text-center py-8 sm:py-12 space-y-3">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <span class="text-xl sm:text-2xl">👤</span>
                                </div>
                                <div>
                                    <p class="text-sm sm:text-base font-medium text-gray-600 dark:text-gray-400">No customer selected</p>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-500">Choose a customer to view their transaction history</p>
                                </div>
                            </div>

                            <!-- No Transactions -->
                            <div v-else-if="transactions.length === 0" class="text-center py-8 sm:py-12 space-y-3">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <span class="text-xl sm:text-2xl">📄</span>
                                </div>
                                <div>
                                    <p class="text-sm sm:text-base font-medium text-gray-600 dark:text-gray-400">No transactions found</p>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-500">This customer has no transaction history yet</p>
                                </div>
                            </div>

                            <!-- Transaction List -->
                            <div v-else class="space-y-3 sm:space-y-4 flex-1 overflow-y-auto">
                                <div 
                                    v-for="transaction in transactions" 
                                    :key="`${transaction.type}-${transaction.id}`"
                                    class="p-3 sm:p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between space-y-3 sm:space-y-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-2">
                                                <span 
                                                    :class="[
                                                        'text-xs font-semibold px-2 sm:px-3 py-1 rounded-full w-fit',
                                                        transaction.type === 'payment' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' :
                                                        transaction.type === 'tracking' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' :
                                                        'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400'
                                                    ]"
                                                >
                                                    {{ getTransactionTypeLabel(transaction.type) }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                    {{ formatDate(transaction.date) }}
                                                </span>
                                            </div>
                                            
                                            <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white mb-3 break-words">
                                                {{ transaction.description }}
                                            </p>

                                            <!-- Sale-specific fields -->
                                            <div v-if="transaction.type === 'sale'" class="space-y-2 mb-3">
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-xs sm:text-sm">
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        Total: <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(transaction.total_amount || 0) }}</span>
                                                    </span>
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        Paid: <span class="font-semibold text-gray-900 dark:text-white">{{ getFormattedPaidAmount(transaction) }}</span>
                                                    </span>
                                                    <Badge :variant="transaction.payment_type === 'cash' ? 'default' : 'destructive'" class="text-xs font-medium w-fit">
                                                        {{ transaction.payment_type?.toUpperCase() }}
                                                    </Badge>
                                                </div>
                                                <div v-if="transaction.notes" class="text-xs text-gray-600 dark:text-gray-400 p-2 bg-gray-100 dark:bg-gray-600 rounded">
                                                    <span class="font-medium">Notes:</span> {{ transaction.notes }}
                                                </div>
                                            </div>
                                            
                                            <!-- Balance changes -->
                                            <div v-if="(transaction.type === 'payment' || transaction.type === 'sale' || transaction.type === 'tracking') && transaction.previous_balance !== undefined" class="text-xs text-gray-600 dark:text-gray-400 p-2 bg-gray-100 dark:bg-gray-600 rounded">
                                                <span v-if="transaction.type === 'tracking'" class="font-medium break-words">
                                                    Previous Month: {{ transaction.formatted_previous_balance }} → Current Month: {{ transaction.formatted_new_balance }}
                                                </span>
                                                <span v-else class="font-medium break-words">
                                                    Balance: {{ transaction.formatted_previous_balance }} → {{ transaction.formatted_new_balance }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3 sm:ml-4">
                                            <!-- View Details Button for Sales -->
                                            <Button
                                                v-if="transaction.type === 'sale'"
                                                variant="ghost"
                                                size="sm"
                                                @click="openSaleDetails(transaction)"
                                                class="text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 w-fit"
                                            >
                                                View Details
                                            </Button>
                                            
                                            <!-- Amount -->
                                            <span class="text-sm sm:text-lg font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 sm:px-3 py-1 rounded-lg w-fit">
                                                {{ transaction.formatted_amount }}
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

