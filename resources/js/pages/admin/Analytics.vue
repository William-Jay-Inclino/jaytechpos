<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'

const breadcrumbs = [
    { title: 'Analytics', href: '/admin/analytics' }
]
</script>

<template>
    <Head title="Analytics" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h2>
                <p class="text-gray-600 mt-1">Track performance, sales trends, and business insights.</p>
            </div>

            <!-- Time Period Selector -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="flex space-x-4">
                    <select class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>Today</option>
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>This Year</option>
                        <option>Custom Range</option>
                    </select>
                </div>
            </div>

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900">₱125,650</p>
                            <p class="text-xs text-green-600 mt-1">+12% from last month</p>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Transactions</p>
                            <p class="text-2xl font-bold text-gray-900">1,247</p>
                            <p class="text-xs text-blue-600 mt-1">+8% from last month</p>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Avg. Order Value</p>
                            <p class="text-2xl font-bold text-gray-900">₱101</p>
                            <p class="text-xs text-purple-600 mt-1">+3% from last month</p>
                        </div>
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Active Users</p>
                            <p class="text-2xl font-bold text-gray-900">24</p>
                            <p class="text-xs text-orange-600 mt-1">+2 from last month</p>
                        </div>
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sales Trend Chart -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Sales Trend</h3>
                        <select class="text-sm border border-gray-300 rounded px-3 py-1">
                            <option>Last 7 days</option>
                            <option>Last 30 days</option>
                            <option>Last 90 days</option>
                        </select>
                    </div>
                    <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                        <p class="text-gray-500">Sales trend chart placeholder</p>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Selling Products</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">Product A</p>
                                <p class="text-sm text-gray-600">125 units sold</p>
                            </div>
                            <p class="text-green-600 font-semibold">₱15,750</p>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">Product B</p>
                                <p class="text-sm text-gray-600">89 units sold</p>
                            </div>
                            <p class="text-green-600 font-semibold">₱11,250</p>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">Product C</p>
                                <p class="text-sm text-gray-600">67 units sold</p>
                            </div>
                            <p class="text-green-600 font-semibold">₱8,340</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Analytics Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Payment Methods -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Cash</span>
                            <div class="flex items-center">
                                <div class="w-20 h-2 bg-gray-200 rounded mr-3">
                                    <div class="w-16 h-2 bg-blue-600 rounded"></div>
                                </div>
                                <span class="text-sm font-medium">80%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Card</span>
                            <div class="flex items-center">
                                <div class="w-20 h-2 bg-gray-200 rounded mr-3">
                                    <div class="w-4 h-2 bg-green-600 rounded"></div>
                                </div>
                                <span class="text-sm font-medium">20%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales by Hour -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Peak Hours</h3>
                    <div class="h-32 bg-gray-100 rounded-lg flex items-center justify-center">
                        <p class="text-gray-500">Hourly sales chart placeholder</p>
                    </div>
                </div>

                <!-- Monthly Goals -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Goal</h3>
                    <div class="text-center">
                        <div class="relative w-24 h-24 mx-auto mb-4">
                            <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-300" stroke="currentColor" stroke-width="2" fill="transparent" d="m18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-blue-600" stroke="currentColor" stroke-width="2" fill="transparent" stroke-dasharray="75, 100" d="m18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-bold text-gray-900">75%</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">₱125k of ₱167k goal</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>