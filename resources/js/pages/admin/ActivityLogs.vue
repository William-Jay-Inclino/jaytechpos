<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'

interface ActivityLog {
    id: number
    user: {
        name: string
        email: string
    }
    action: string
    description: string
    ip_address: string
    created_at: string
}

interface Props {
    logs: {
        data: ActivityLog[]
        links: any[]
        meta: any
    }
}

defineProps<Props>()

const breadcrumbs = [
    { title: 'Activity Logs', href: '/admin/activity-logs' }
]

const getActionBadgeClass = (action: string) => {
    switch (action.toLowerCase()) {
        case 'create':
        case 'created':
            return 'bg-green-100 text-green-800'
        case 'update':
        case 'updated':
            return 'bg-blue-100 text-blue-800'
        case 'delete':
        case 'deleted':
            return 'bg-red-100 text-red-800'
        case 'login':
            return 'bg-purple-100 text-purple-800'
        case 'logout':
            return 'bg-gray-100 text-gray-800'
        default:
            return 'bg-yellow-100 text-yellow-800'
    }
}
</script>

<template>
    <Head title="Activity Logs" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Activity Logs</h2>
                    <p class="text-gray-600 mt-1">Monitor user activities and system events.</p>
                </div>
                <div class="flex space-x-3">
                    <select class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Actions</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                    </select>
                    <select class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="flex items-center space-x-4">
                    <div>
                        <input
                            type="text"
                            placeholder="Search by user or action..."
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64"
                        >
                    </div>
                    <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        Search
                    </button>
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activities</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- Sample Data - Remove when implementing real data -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">John Doe</div>
                                        <div class="text-sm text-gray-500">john@example.com</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Create
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">Created new product: iPhone 15</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    192.168.1.100
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    2 minutes ago
                                </td>
                            </tr>

                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">Jane Smith</div>
                                        <div class="text-sm text-gray-500">jane@example.com</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Update
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">Updated customer information for Maria Santos</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    192.168.1.105
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    5 minutes ago
                                </td>
                            </tr>

                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">Admin User</div>
                                        <div class="text-sm text-gray-500">admin@jaytechpos.com</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Login
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">User logged into admin panel</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    192.168.1.1
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    10 minutes ago
                                </td>
                            </tr>

                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">Bob Wilson</div>
                                        <div class="text-sm text-gray-500">bob@example.com</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Delete
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">Deleted expired product: Samsung Galaxy S20</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    192.168.1.110
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    15 minutes ago
                                </td>
                            </tr>

                            <!-- Dynamic data when implemented -->
                            <tr v-for="log in logs?.data || []" :key="log.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ log.user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ log.user.email }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                        getActionBadgeClass(log.action)
                                    ]">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ log.description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ log.ip_address }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ log.created_at }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Placeholder -->
                <div class="px-6 py-4 border-t bg-gray-50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600">Showing 1 to 10 of 100+ results</p>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100">Previous</button>
                            <button class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100">Next</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Today's Activities</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Logins</span>
                            <span class="text-sm font-medium">24</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Sales</span>
                            <span class="text-sm font-medium">156</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Updates</span>
                            <span class="text-sm font-medium">12</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Most Active Users</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">John Doe</span>
                            <span class="text-sm font-medium">45 actions</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Jane Smith</span>
                            <span class="text-sm font-medium">32 actions</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Bob Wilson</span>
                            <span class="text-sm font-medium">28 actions</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <button class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded">
                            Export Logs
                        </button>
                        <button class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded">
                            Clear Old Logs
                        </button>
                        <button class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded">
                            Activity Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>