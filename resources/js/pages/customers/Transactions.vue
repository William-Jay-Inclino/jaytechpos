<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CustomerTransactionHistory from '@/components/CustomerTransactionHistory.vue';
import { Customer, CustomerTransaction, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    customer: Customer;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
    {
        title: props.customer.name,
        href: `/customers/${props.customer.id}/transactions`,
    },
];

// Transaction history state
const transactions = ref<CustomerTransaction[]>([]);
const loadingTransactions = ref(false);

// Methods
const fetchCustomerTransactions = async () => {
    loadingTransactions.value = true;
    try {
        // Get CSRF token from the meta tag
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/api/customers/${props.customer.id}/transactions`,
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
        transactions.value = [];
    } finally {
        loadingTransactions.value = false;
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
};

// Load transactions on mount
onMounted(() => {
    fetchCustomerTransactions();
});
</script>

<template>
    <Head :title="`${customer.name} - Transactions`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1
                        class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white"
                    >
                        📋 {{ customer.name }}'s Transactions
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Complete transaction history for this customer
                    </p>
                </div>

                <!-- Customer Summary Card -->
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        👤 Customer Information
                    </h2>
                    
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Customer Name
                            </p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ customer.name }}
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Mobile Number
                            </p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ customer.mobile_number || 'N/A' }}
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Interest Rate
                            </p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ customer.effective_interest_rate?.toFixed(2) || '0.00' }}%
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Current Balance
                            </p>
                            <p 
                                :class="[
                                    'text-lg font-bold',
                                    customer.running_utang_balance > 0 
                                        ? 'text-red-600 dark:text-red-400' 
                                        : 'text-green-600 dark:text-green-400'
                                ]"
                            >
                                {{ formatCurrency(customer.running_utang_balance || 0) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="customer.remarks" class="mt-4 rounded bg-gray-50 p-3 dark:bg-gray-700">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Remarks
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ customer.remarks }}
                        </p>
                    </div>
                </div>

                <!-- Transaction History -->
                <CustomerTransactionHistory
                    :transactions="transactions"
                    :loading="loadingTransactions"
                    :customer-name="customer.name"
                />
            </div>
        </div>
    </AppLayout>
</template>