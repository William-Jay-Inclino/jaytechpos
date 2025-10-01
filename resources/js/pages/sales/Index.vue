<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LucideEye } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sales',
        href: '/sales',
    },
];

const sales = [
    { id: 1, invoice_number: 'INV-001', customer_name: 'John Doe', date: '2025-10-01', total: '1,200.00' },
    { id: 2, invoice_number: 'INV-002', customer_name: 'Jane Smith', date: '2025-09-30', total: '850.00' },
    { id: 3, invoice_number: 'INV-003', customer_name: 'Michael Cruz', date: '2025-09-28', total: '2,500.00' },
];
</script>

<template>
    <Head title="Sales" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <h1 class="text-lg sm:text-xl lg:text-2xl font-semibold tracking-tight">
                    Sales
                </h1>
                <Link href="/sales/create" class="w-full sm:w-auto">
                    <Button class="w-full sm:w-auto shadow-md">New Sale</Button>
                </Link>
            </div>

            <!-- Search + Filters -->
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <Input type="text" placeholder="Search sales..." class="sm:max-w-xs" />
                <div class="flex gap-2">
                    <Button variant="outline" size="sm">Today</Button>
                    <Button variant="outline" size="sm">This Week</Button>
                    <Button variant="outline" size="sm">This Month</Button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-white dark:bg-gray-900 dark:border-gray-700 shadow-sm">
                <Table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <TableHeader>
                        <TableRow class="bg-gray-50 dark:bg-gray-800">
                            <TableHead class="px-4 sm:px-6 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                                Invoice #
                            </TableHead>
                            <TableHead class="px-4 sm:px-6 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                                Customer
                            </TableHead>
                            <TableHead class="px-4 sm:px-6 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                                Date
                            </TableHead>
                            <TableHead class="px-4 sm:px-6 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">
                                Total
                            </TableHead>
                            <TableHead class="px-4 sm:px-6 py-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300 w-32">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="sale in sales"
                            :key="sale.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        >
                            <TableCell class="px-4 sm:px-6 py-4 font-medium text-gray-800 dark:text-gray-100">
                                {{ sale.invoice_number }}
                            </TableCell>
                            <TableCell class="px-4 sm:px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ sale.customer_name }}
                            </TableCell>
                            <TableCell class="px-4 sm:px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ sale.date }}
                            </TableCell>
                            <TableCell class="px-4 sm:px-6 py-4 text-gray-600 dark:text-gray-300">
                                ₱ {{ sale.total }}
                            </TableCell>
                            <TableCell class="px-4 sm:px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <Link :href="`/sales/${sale.id}`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-500">
                                            <LucideEye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!sales.length">
                            <TableCell colspan="5" class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                No sales found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
