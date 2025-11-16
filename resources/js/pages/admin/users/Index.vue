<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'

interface User {
    id: number
    name: string
    email: string
    role: string
    created_at: string
}

interface Props {
    users: {
        data: User[]
        links: any[]
        meta: any
    }
}

defineProps<Props>()

const breadcrumbs = [
    { title: 'Users', href: '/admin/users' }
]

const deleteUser = (userId: number) => {
    if (confirm('Are you sure you want to delete this user?')) {
        // Delete functionality to be implemented
        console.log('Delete user:', userId)
    }
}
</script>

<template>
    <Head title="User Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                    <p class="text-gray-600 mt-1">Manage system users and their roles.</p>
                </div>
                <a
                    href="/admin/users/create"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add User
                </a>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">All Users</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            user.role === 'Administrator' 
                                                ? 'bg-purple-100 text-purple-800'
                                                : 'bg-green-100 text-green-800'
                                        ]"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.created_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a
                                            :href="`/admin/users/${user.id}/edit`"
                                            class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50"
                                        >
                                            Edit
                                        </a>
                                        <button
                                            class="text-red-600 hover:text-red-900 px-2 py-1 rounded hover:bg-red-50"
                                            @click="deleteUser(user.id)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Placeholder -->
                <div class="px-6 py-4 border-t bg-gray-50">
                    <p class="text-sm text-gray-600">Pagination will be implemented here</p>
                </div>
            </div>

            <!-- Empty State (if no users) -->
            <div v-if="users.data.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mt-4">No users found</h3>
                <p class="text-gray-500 mt-2">Get started by creating your first user.</p>
                <a
                    href="/admin/users/create"
                    class="inline-flex items-center px-4 py-2 mt-4 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                >
                    Add User
                </a>
            </div>
        </div>
    </AdminLayout>
</template>