<script setup lang="ts">
import { Head, Form } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface User {
    id: number
    name: string
    email: string
    role: string
}

interface Props {
    user: User
}

defineProps<Props>()

const breadcrumbs = [
    { title: 'Profile Settings', href: '/admin/profile' }
]
</script>

<template>
    <Head title="Profile Settings" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Profile Settings</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your account information and security settings.</p>
            </div>

            <!-- Basic Information -->
            <Card>
                <CardHeader>
                    <CardTitle>Basic Information</CardTitle>
                    <CardDescription>Update your account details.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        action="/admin/profile"
                        method="patch"
                        #default="{ errors, processing }"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name">Full Name</Label>
                                <Input
                                    type="text"
                                    name="name"
                                    id="name"
                                    :value="user.name"
                                    placeholder="Enter your full name"
                                    required
                                />
                                <p v-if="errors.name" class="text-sm text-red-600 dark:text-red-400">{{ errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <Label for="email">Email Address</Label>
                                <Input
                                    type="email"
                                    name="email"
                                    id="email"
                                    :value="user.email"
                                    placeholder="Enter your email"
                                    required
                                />
                                <p v-if="errors.email" class="text-sm text-red-600 dark:text-red-400">{{ errors.email }}</p>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="mt-6 flex justify-end">
                            <Button type="submit" :disabled="processing">
                                <span v-if="processing">Updating...</span>
                                <span v-else>Update Information</span>
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <!-- Change Password -->
            <Card>
                <CardHeader>
                    <CardTitle>Change Password</CardTitle>
                    <CardDescription>Update your password to keep your account secure.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        action="/admin/profile/password"
                        method="patch"
                        #default="{ errors, processing }"
                    >
                        <div class="space-y-4">
                            <!-- Current Password -->
                            <div class="space-y-2">
                                <Label for="current_password">Current Password</Label>
                                <Input
                                    type="password"
                                    name="current_password"
                                    id="current_password"
                                    placeholder="Enter your current password"
                                    required
                                />
                                <p v-if="errors.current_password" class="text-sm text-red-600 dark:text-red-400">{{ errors.current_password }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- New Password -->
                                <div class="space-y-2">
                                    <Label for="password">New Password</Label>
                                    <Input
                                        type="password"
                                        name="password"
                                        id="password"
                                        placeholder="Enter new password"
                                        required
                                    />
                                    <p v-if="errors.password" class="text-sm text-red-600 dark:text-red-400">{{ errors.password }}</p>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="space-y-2">
                                    <Label for="password_confirmation">Confirm New Password</Label>
                                    <Input
                                        type="password"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        placeholder="Confirm new password"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="mt-6 flex justify-end">
                            <Button type="submit" :disabled="processing">
                                <span v-if="processing">Updating...</span>
                                <span v-else>Update Password</span>
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>