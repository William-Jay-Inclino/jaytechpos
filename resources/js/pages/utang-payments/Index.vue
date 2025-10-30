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
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <h1 class="text-lg sm:text-xl lg:text-2xl font-semibold tracking-tight">
                    Utang Payment
                </h1>
            </div>

            <!-- Main Content - Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Left Column - Payment Form -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Record Payment</CardTitle>
                            <CardDescription>
                                Record a new utang payment for a customer
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action="/utang-payments"
                                method="post"
                                :reset-on-success="true"
                                v-slot="{ errors, processing }"
                                @success="handleFormSuccess"
                                @error="handleFormError"
                                class="space-y-4"
                            >
                                <!-- Customer Selection -->
                                <div class="space-y-2">
                                    <Label for="customer_id">Customer *</Label>
                                    <Select v-model="selectedCustomerId" name="customer_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a customer" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem 
                                                v-for="customer in customers" 
                                                :key="customer.id" 
                                                :value="customer.id.toString()"
                                            >
                                                {{ customer.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="errors.customer_id" class="text-sm text-red-600">
                                        {{ errors.customer_id }}
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="space-y-2">
                                    <Label for="payment_amount">Payment Amount *</Label>
                                    <Input
                                        id="payment_amount"
                                        name="payment_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        v-model="paymentAmount"
                                    />
                                    <div v-if="errors.payment_amount" class="text-sm text-red-600">
                                        {{ errors.payment_amount }}
                                    </div>
                                </div>

                                <!-- Payment Date -->
                                <div class="space-y-2">
                                    <Label for="payment_date">Payment Date & Time *</Label>
                                    <Input
                                        id="payment_date"
                                        name="payment_date"
                                        type="datetime-local"
                                        v-model="paymentDate"
                                    />
                                    <div v-if="errors.payment_date" class="text-sm text-red-600">
                                        {{ errors.payment_date }}
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="space-y-2">
                                    <Label for="notes">Notes</Label>
                                    <Textarea
                                        id="notes"
                                        name="notes"
                                        placeholder="Optional notes about this payment..."
                                        rows="3"
                                        maxlength="1000"
                                        v-model="notes"
                                    />
                                    <div v-if="errors.notes" class="text-sm text-red-600">
                                        {{ errors.notes }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <Button 
                                    type="submit" 
                                    :disabled="processing || !isFormValid"
                                    class="w-full"
                                >
                                    <span v-if="processing">Recording Payment...</span>
                                    <span v-else>Record Payment</span>
                                </Button>
                            </Form>
                        </CardContent>
                    </Card>

                    <!-- Customer Information Card -->
                    <Card v-if="selectedCustomer">
                        <CardHeader>
                            <CardTitle>Customer Information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Mobile Number:</span>
                                    <span class="text-sm font-medium">{{ selectedCustomer.mobile_number || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Interest Rate:</span>
                                    <span class="text-sm font-medium">{{ selectedCustomer.effective_interest_rate?.toFixed(2) || '0.00' }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Current Balance:</span>
                                    <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                        {{ formatCurrency(selectedCustomer.running_utang_balance || 0) }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Transaction History -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle>Transaction History</CardTitle>
                            <CardDescription>
                                <span v-if="selectedCustomer">
                                    Recent transactions for {{ selectedCustomer.name }}
                                </span>
                                <span v-else>
                                    Select a customer to view transaction history
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Loading State -->
                            <div v-if="loadingTransactions" class="flex items-center justify-center py-8">
                                <div class="text-sm text-muted-foreground">Loading transactions...</div>
                            </div>

                            <!-- No Customer Selected -->
                            <div v-else-if="!selectedCustomer" class="flex items-center justify-center py-8">
                                <div class="text-sm text-muted-foreground">No customer selected</div>
                            </div>

                            <!-- No Transactions -->
                            <div v-else-if="transactions.length === 0" class="flex items-center justify-center py-8">
                                <div class="text-sm text-muted-foreground">No transactions found</div>
                            </div>

                            <!-- Transaction List -->
                            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                                <div 
                                    v-for="transaction in transactions" 
                                    :key="`${transaction.type}-${transaction.id}`"
                                    class="p-3 bg-muted/30 rounded-lg border"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span 
                                                    :class="getTransactionTypeColor(transaction.type)"
                                                    class="text-xs font-medium px-2 py-1 rounded-full bg-muted"
                                                >
                                                    {{ getTransactionTypeLabel(transaction.type) }}
                                                </span>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ formatDate(transaction.date) }}
                                                </span>
                                            </div>
                                            
                                            <p class="text-sm font-medium truncate mb-2">
                                                {{ transaction.description }}
                                            </p>

                                            <!-- Sale-specific fields -->
                                            <div v-if="transaction.type === 'sale'" class="space-y-1 mb-2">
                                                <div class="flex items-center gap-4 text-xs">
                                                    <span class="text-muted-foreground">
                                                        Total: {{ formatCurrency(transaction.total_amount || 0) }}
                                                    </span>
                                                    <span class="text-muted-foreground">
                                                        Paid: {{ getFormattedPaidAmount(transaction) }}
                                                    </span>
                                                    <Badge :variant="transaction.payment_type === 'cash' ? 'default' : 'destructive'" class="text-xs">
                                                        {{ transaction.payment_type?.toUpperCase() }}
                                                    </Badge>
                                                </div>
                                                <div v-if="transaction.notes" class="text-xs text-muted-foreground">
                                                    Notes: {{ transaction.notes }}
                                                </div>
                                            </div>
                                            
                                            <!-- Balance changes -->
                                            <div v-if="(transaction.type === 'payment' || transaction.type === 'sale' || transaction.type === 'tracking') && transaction.previous_balance !== undefined" class="text-xs text-muted-foreground">
                                                <span v-if="transaction.type === 'tracking'">
                                                    Previous Month: {{ transaction.formatted_previous_balance }} → Current Month: {{ transaction.formatted_new_balance }}
                                                </span>
                                                <span v-else>
                                                    Balance: {{ transaction.formatted_previous_balance }} → {{ transaction.formatted_new_balance }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 ml-4">
                                            <!-- View Details Button for Sales -->
                                            <button 
                                                v-if="transaction.type === 'sale'"
                                                @click="openSaleDetails(transaction)"
                                                class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                            >
                                                View Details
                                            </button>
                                            
                                            <!-- Amount -->
                                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                {{ transaction.formatted_amount }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
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

