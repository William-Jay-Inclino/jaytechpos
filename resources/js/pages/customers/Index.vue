<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Customer, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, withDefaults } from 'vue';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

// Search and filter state
const searchQuery = ref('');
const statusFilter = ref('all');

// Filtered customers based on search and filter
const filteredCustomers = computed(() => {
    if (!Array.isArray(props.customers)) return [];
    
    let filtered = props.customers;
    
    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        filtered = filtered.filter(customer => 
            customer.name.toLowerCase().includes(query) ||
            (customer.mobile_number && customer.mobile_number.toLowerCase().includes(query))
        );
    }
    
    // Apply status filter
    if (statusFilter.value === 'has_balance') {
        filtered = filtered.filter(customer => customer.has_utang);
    } else if (statusFilter.value === 'clear') {
        filtered = filtered.filter(customer => !customer.has_utang);
    }
    
    return filtered;
});

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
                    
                    <Button as-child>
                        <Link href="/customers/create">
                            ➕ Add New Customer
                        </Link>
                    </Button>
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

                <!-- Search and Filters -->
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex-1 sm:min-w-[300px]">
                                <Input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name or mobile number..."
                                    class="w-full"
                                />
                            </div>
                            
                            <div class="sm:min-w-[180px]">
                                <Select v-model="statusFilter">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Filter by status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Customers</SelectItem>
                                        <SelectItem value="has_balance">Has Balance</SelectItem>
                                        <SelectItem value="clear">Clear</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ filteredCustomers.length }} of {{ totalCustomers }} customers
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
                            <Button as-child>
                                <Link href="/customers/create">
                                    ➕ Add Customer
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div v-else-if="filteredCustomers.length === 0 && searchQuery.trim()" class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-2xl">🔍</span>
                        </div>
                        <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
                            No customers found
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Try adjusting your search or filter criteria.
                        </p>
                        <div class="mt-6">
                            <Button variant="outline" @click="searchQuery = ''; statusFilter = 'all'">
                                Clear Filters
                            </Button>
                        </div>
                    </div>

                    <Table v-else-if="filteredCustomers.length > 0">
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
                            <TableRow v-for="customer in filteredCustomers" :key="customer.id">
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
                                        <Button size="sm" variant="outline" as-child>
                                            <Link :href="`/customers/${customer.id}/edit`">
                                                ✏️ Edit
                                            </Link>
                                        </Button>
                                        
                                        <Button size="sm" as-child>
                                            <Link :href="`/customers/${customer.id}/transactions`">
                                                📋 Transactions
                                            </Link>
                                        </Button>
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