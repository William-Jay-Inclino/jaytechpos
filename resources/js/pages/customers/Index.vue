<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Customer, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, withDefaults } from 'vue';
import { Search, Edit, FileText } from 'lucide-vue-next';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

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
        <div class="w-full px-3 py-4 lg:px-8 lg:py-10">
            <!-- Page Header -->
            <div class="mx-auto max-w-7xl">
                <div class="mb-4 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
                    
                    <Button as-child class="w-full sm:w-auto">
                        <Link href="/customers/create" class="flex items-center justify-center gap-2">
                            <span>Add Customer</span>
                        </Link>
                    </Button>
                </div>

                <!-- Search and Filters -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search here..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="flex gap-1.5 sm:gap-2 overflow-x-auto">
                            <button v-for="status in [
                                { key: 'all', label: 'All', count: totalCustomers, color: 'blue' },
                                { key: 'has_balance', label: 'Has Balance', count: customersWithUtang, color: 'red' },
                                { key: 'clear', label: 'Clear', count: totalCustomers - customersWithUtang, color: 'green' }
                            ]" :key="status.key" @click="statusFilter = status.key" :class="[
                                'px-2.5 py-1.5 sm:px-3 rounded-lg text-xs sm:text-sm font-medium transition-colors whitespace-nowrap',
                                statusFilter === status.key
                                    ? `bg-${status.color}-600 text-white`
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                            ]">
                                {{ status.label }} ({{ status.count }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Customers Display -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <!-- Empty State - No Customers -->
                    <div v-if="!Array.isArray(customers) || customers.length === 0" class="px-4 py-16 sm:px-6 sm:py-20 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl">👥</span>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No customers yet
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Get started by adding your first customer to manage their accounts.
                        </p>
                        <div class="mt-8">
                            <Button as-child size="lg">
                                <Link href="/customers/create" class="flex items-center gap-2">
                                    <span class="text-lg">+</span>
                                    Add Your First Customer
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State - No Results -->
                    <div v-else-if="filteredCustomers.length === 0 && (searchQuery.trim() || statusFilter !== 'all')" class="px-4 py-16 sm:px-6 sm:py-20 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <Search class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No customers found
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Try adjusting your search terms or filter criteria to find what you're looking for.
                        </p>
                        <div class="mt-8">
                            <Button variant="outline" @click="searchQuery = ''; statusFilter = 'all'" size="lg">
                                Clear All Filters
                            </Button>
                        </div>
                    </div>

                    <!-- Customers List -->
                    <div v-else>

                        <!-- Mobile Cards -->
                        <div class="block lg:hidden divide-y divide-gray-100 dark:divide-gray-700">
                            <div
                                v-for="customer in filteredCustomers"
                                :key="customer.id"
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors space-y-3"
                            >
                                <!-- Header Row -->
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                            {{ customer.name }}
                                        </h3>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <Badge 
                                                :variant="customer.has_utang ? 'destructive' : 'default'"
                                                :class="customer.has_utang ? '' : 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-800'"
                                                class="text-xs"
                                            >
                                                {{ customer.has_utang ? 'Has Balance' : 'Clear' }}
                                            </Badge>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ customer.mobile_number || 'No phone' }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ customer.effective_interest_rate?.toFixed(2) || '0.00' }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                            Balance
                                        </div>
                                        <div 
                                            :class="[
                                                'text-lg font-bold',
                                                customer.running_utang_balance > 0 
                                                    ? 'text-red-600 dark:text-red-400' 
                                                    : 'text-green-600 dark:text-green-400'
                                            ]"
                                        >
                                            {{ formatCurrency(customer.running_utang_balance || 0) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks (if any) -->
                                <div v-if="customer.remarks" class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                                        "{{ customer.remarks.length > 60 ? customer.remarks.substring(0, 60) + '...' : customer.remarks }}"
                                    </p>
                                </div>

                                <!-- Actions Row -->
                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        as-child
                                        class="h-8 px-3"
                                    >
                                        <Link :href="`/customers/${customer.id}/edit`">
                                            <Edit class="h-3 w-3 mr-1.5" />
                                        </Link>
                                    </Button>
                                    
                                    <Button 
                                        size="sm" 
                                        variant="default"
                                        as-child
                                        class="h-8 px-3 bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        <Link :href="`/customers/${customer.id}/transactions`">
                                            <FileText class="h-3 w-3 mr-1.5 text-white" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden lg:block">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-3 pl-6 pr-4 w-80 text-left text-sm font-medium text-gray-900 dark:text-white">
                                            Customer
                                        </th>
                                        <th class="py-3 px-4 w-40 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            Balance
                                        </th>
                                        <th class="py-3 pl-4 pr-6 w-64 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="customer in filteredCustomers" 
                                        :key="customer.id"
                                        class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <td class="py-4 pl-6 pr-4 w-80">
                                            <div class="flex items-center gap-3">
                                                <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ customer.name }}
                                                </h3>
                                                <span class="text-base text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    {{ customer.mobile_number || 'No phone' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1">
                                                <Badge 
                                                    :variant="customer.has_utang ? 'destructive' : 'default'"
                                                    :class="customer.has_utang ? '' : 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-800'"
                                                    class="text-xs"
                                                >
                                                    {{ customer.has_utang ? 'Has Balance' : 'Clear' }}
                                                </Badge>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    {{ customer.effective_interest_rate?.toFixed(2) || '0.00' }}% interest
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 w-40 text-right">
                                            <div 
                                                :class="[
                                                    'text-lg font-bold',
                                                    customer.running_utang_balance > 0 
                                                        ? 'text-red-600 dark:text-red-400' 
                                                        : 'text-green-600 dark:text-green-400'
                                                ]"
                                            >
                                                {{ formatCurrency(customer.running_utang_balance || 0) }}
                                            </div>
                                        </td>
                                        <td class="py-4 pl-4 pr-6 w-64">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button size="sm" variant="outline" as-child>
                                                    <Link :href="`/customers/${customer.id}/edit`">
                                                        <Edit class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                
                                                <Button 
                                                    size="sm" 
                                                    variant="default"
                                                    as-child
                                                    class="bg-blue-600 hover:bg-blue-700 text-white"
                                                >
                                                    <Link :href="`/customers/${customer.id}/transactions`">
                                                        <FileText class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>