<script setup lang="ts">
import { Head, Form } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'

interface Role {
    value: string
    label: string
}

interface Props {
    roles: Role[]
}

defineProps<Props>()

const breadcrumbs = [
    { title: 'Users', href: '/admin/users' },
    { title: 'Create User', href: '/admin/users/create' }
]
</script>

<template>
    <Head title="Create User" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Create New User</h2>
                <p class="text-gray-600 mt-1">Add a new user to the system.</p>
            </div>

            <!-- Create Form -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
                </div>
                
                <Form
                    action="/admin/users"
                    method="post"
                    #default="{ errors, processing }"
                    class="p-6"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter full name"
                            >
                            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter email address"
                            >
                            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter password"
                            >
                            <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <input
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Confirm password"
                            >
                        </div>

                        <!-- Role -->
                        <div class="md:col-span-2">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                                Role
                            </label>
                            <select
                                name="role"
                                id="role"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select a role</option>
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role }}</p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <a
                            href="/admin/users"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            :disabled="processing"
                            :class="[
                                'px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                processing 
                                    ? 'bg-gray-400 cursor-not-allowed' 
                                    : 'bg-blue-600 hover:bg-blue-700'
                            ]"
                        >
                            <span v-if="processing">Creating...</span>
                            <span v-else>Create User</span>
                        </button>
                    </div>
                </Form>
            </div>
        </div>
    </AdminLayout>
</template>