<script setup lang="ts">
import { CustomerTransaction } from '@/types';
import { formatManilaDateTime, formatPhilippinePeso } from '@/utils/timezone';
import { ref } from 'vue';

// UI Components
import SaleDetailsModal from '@/components/modals/SaleDetailsModal.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    transactions: CustomerTransaction[];
    loading?: boolean;
    customerName?: string;
    customerId?: number;
}>();

// Modal state
const showSaleModal = ref(false);
const selectedSaleTransaction = ref<CustomerTransaction | null>(null);
const loadingTransactionIds = ref<Set<number>>(new Set());

// Methods
const formatCurrency = formatPhilippinePeso;
const formatDate = formatManilaDateTime;

const getTransactionTypeLabel = (type: string) => {
    switch (type) {
        case 'utang_payment':
            return 'Utang Payment';
        case 'monthly_interest':
            return 'Monthly Interest';
        case 'sale':
            return 'Sale Transaction';
        case 'starting_balance':
            return 'Starting Balance';
        case 'balance_update':
            return 'Balance Update';
        default:
            return 'Unknown';
    }
};

const openSaleDetails = async (transaction: CustomerTransaction) => {
    if (transaction.type !== 'sale' || !props.customerId) return;
    
    loadingTransactionIds.value.add(transaction.id);
    try {
        // Get CSRF token from the meta tag
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/api/customers/${props.customerId}/transactions/${transaction.id}`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        selectedSaleTransaction.value = data.transaction;
        showSaleModal.value = true;
    } catch (error) {
        console.error('Error fetching transaction details:', error);
    } finally {
        loadingTransactionIds.value.delete(transaction.id);
    }
};

const isTransactionLoading = (transactionId: number) => {
    return loadingTransactionIds.value.has(transactionId);
};
</script>

<template>
    <div
        class="flex flex-1 flex-col rounded-xl border border-gray-300 bg-white p-4 shadow-lg ring-1 ring-gray-100 sm:p-6 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
    >
        <h2
            class="mb-3 text-base font-semibold text-gray-900 sm:mb-4 sm:text-lg dark:text-white"
        >
            📋 Transaction History
        </h2>
        <p
            class="mb-4 text-xs text-gray-600 sm:mb-6 sm:text-sm dark:text-gray-400"
        >
            <span v-if="customerName">
                Recent transactions for
                <span class="font-semibold">{{ customerName }}</span>
            </span>
            <span v-else>
                Select a customer to view transaction history
            </span>
        </p>

        <!-- Loading State -->
        <div
            v-if="loading"
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
            v-else-if="!customerName"
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
                    Choose a customer to view their transaction history
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
                    This customer has no transaction history yet
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
                                    transaction.type === 'utang_payment'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                        : transaction.type === 'monthly_interest'
                                          ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
                                          : transaction.type === 'starting_balance'
                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400'
                                            : transaction.type === 'balance_update'
                                              ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400'
                                              : 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
                                ]"
                            >
                                {{ getTransactionTypeLabel(transaction.type) }}
                            </span>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(transaction.date) }}
                            </span>
                        </div>

                        <p
                            class="mb-3 text-xs font-semibold break-words text-gray-900 sm:text-sm dark:text-white"
                        >
                            {{ transaction.description }}
                        </p>

                        <!-- Balance changes -->
                        <div
                            v-if="
                                (transaction.type === 'utang_payment' ||
                                    transaction.type === 'sale' ||
                                    transaction.type === 'monthly_interest' ||
                                    transaction.type === 'starting_balance' ||
                                    transaction.type === 'balance_update') &&
                                transaction.previous_balance !== undefined
                            "
                            class="rounded bg-gray-100 p-2 text-xs text-gray-600 dark:bg-gray-600 dark:text-gray-400"
                        >
                            <span
                                v-if="transaction.type === 'monthly_interest'"
                                class="font-medium break-words"
                            >
                                Previous Month:
                                {{ transaction.formatted_previous_balance }}
                                → Current Month:
                                {{ transaction.formatted_new_balance }}
                            </span>
                            <span
                                v-else-if="transaction.type === 'starting_balance'"
                                class="font-medium break-words"
                            >
                                Initial Balance:
                                {{ transaction.formatted_previous_balance }}
                                → Starting Balance:
                                {{ transaction.formatted_new_balance }}
                            </span>
                            <span
                                v-else
                                class="font-medium break-words"
                            >
                                Balance:
                                {{ transaction.formatted_previous_balance }}
                                →
                                {{ transaction.formatted_new_balance }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex flex-col items-start gap-2 sm:ml-4 sm:flex-row sm:items-center sm:gap-3"
                    >
                        <!-- View Details Button for Sales -->
                        <Button
                            v-if="transaction.type === 'sale'"
                            variant="ghost"
                            size="sm"
                            @click="openSaleDetails(transaction)"
                            :disabled="isTransactionLoading(transaction.id)"
                            class="w-fit text-xs text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                        >
                            <span v-if="isTransactionLoading(transaction.id)" class="flex items-center gap-1">
                                <div class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent"></div>
                                Loading...
                            </span>
                            <span v-else>View Details</span>
                        </Button>

                        <!-- Amount -->
                        <span
                            class="w-fit rounded-lg bg-green-50 px-2 py-1 text-sm font-bold text-green-600 sm:px-3 sm:text-lg dark:bg-green-900/20 dark:text-green-400"
                        >
                            {{ transaction.formatted_amount }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sale Details Modal -->
        <SaleDetailsModal
            v-model:open="showSaleModal"
            :transaction="selectedSaleTransaction as any"
        />
    </div>
</template>