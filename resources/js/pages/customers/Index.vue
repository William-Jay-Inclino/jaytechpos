<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Customer, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, withDefaults } from 'vue';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = withDefaults(defineProps<{
    customers: Array<Customer>;
}>(), {
    customers: () => []
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
];

// Computed properties
const customersWithUtang = computed(() => 
    Array.isArray(props.customers) ? props.customers.filter(customer => customer.has_utang).length : 0
);

const totalCustomers = computed(() => Array.isArray(props.customers) ? props.customers.length : 0);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(amount);
};
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <!-- Page Header -->
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white"
                        >
                            👥 Customers
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Manage your customer information and track their accounts
                        </p>
                    </div>
                    
                    <Link
                        href="/customers/create"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        ➕ Add New Customer
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">👥</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total Customers
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ totalCustomers }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">💳</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    With Outstanding Balance
                                </p>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ customersWithUtang }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Customer List
                        </h3>
                    </div>

                    <div v-if="!Array.isArray(customers) || customers.length === 0" class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-2xl">👥</span>
                        </div>
                        <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
                            No customers yet
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Get started by adding your first customer.
                        </p>
                        <div class="mt-6">
                            <Link
                                href="/customers/create"
                                class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                            >
                                ➕ Add Customer
                            </Link>
                        </div>
                    </div>

                    <Table v-else-if="Array.isArray(customers) && customers.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Name</TableHead>
                                <TableHead>Mobile Number</TableHead>
                                <TableHead>Interest Rate</TableHead>
                                <TableHead>Current Balance</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Remarks</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="customer in customers" :key="customer.id">
                                <TableCell class="font-medium">
                                    {{ customer.name }}
                                </TableCell>
                                <TableCell>
                                    {{ customer.mobile_number || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    {{ customer.effective_interest_rate?.toFixed(2) || '0.00' }}%
                                </TableCell>
                                <TableCell>
                                    <span 
                                        :class="[
                                            'font-semibold',
                                            customer.running_utang_balance > 0 
                                                ? 'text-red-600 dark:text-red-400' 
                                                : 'text-green-600 dark:text-green-400'
                                        ]"
                                    >
                                        {{ formatCurrency(customer.running_utang_balance || 0) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="customer.has_utang ? 'destructive' : 'secondary'"
                                    >
                                        {{ customer.has_utang ? 'Has Balance' : 'Clear' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span 
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        :title="customer.remarks || undefined"
                                    >
                                        {{ customer.remarks ? (customer.remarks.length > 30 ? customer.remarks.substring(0, 30) + '...' : customer.remarks) : 'N/A' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/customers/${customer.id}/edit`"
                                            class="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-700"
                                        >
                                            ✏️ Edit
                                        </Link>
                                        
                                        <Link
                                            :href="`/customers/${customer.id}/transactions`"
                                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
                                        >
                                            📋 Transactions
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>