<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface User {
    id: number
    name: string
    email: string
    role: string
    status: string
}

interface Props {
    user: User
}

const props = defineProps<Props>();

// const breadcrumbs = [
//     { title: 'Users', href: '/admin/users' },
//     { title: 'Edit User', href: '' }
// ];

// Form for updating user information
const infoForm = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
});

// Form for updating password
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

// Form for updating status
const statusForm = useForm({
    status: props.user.status,
});
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AdminLayout>
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-2xl space-y-6">

                <!-- User Information Form -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Information</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update the user's name and email address.</p>
                    </div>

                    <form @submit.prevent="infoForm.put(`/admin/users/${user.id}`, { onSuccess: () => { showSuccessToast('User information updated successfully!'); } })" class="space-y-6">
                        <!-- Name -->
                        <div class="grid gap-2">
                            <Label for="name">Full Name</Label>
                            <Input
                                id="name"
                                v-model="infoForm.name"
                                type="text"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="infoForm.errors.name" class="mt-1" />
                        </div>

                        <!-- Email -->
                        <div class="grid gap-2">
                            <Label for="email">Email Address</Label>
                            <Input
                                id="email"
                                v-model="infoForm.email"
                                type="email"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="infoForm.errors.email" class="mt-1" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="infoForm.processing">
                                {{ infoForm.processing ? 'Updating...' : 'Update' }}
                            </Button>
                            <Link href="/admin/users">
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>

                <!-- Change Status Form -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Status</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update the user's account status.</p>
                    </div>

                    <form @submit.prevent="statusForm.put(`/admin/users/${user.id}`, { onSuccess: () => { showSuccessToast('User status updated successfully!'); } })" class="space-y-6">
                        <!-- Status -->
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="statusForm.status">
                                <SelectTrigger class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="statusForm.errors.status" class="mt-1" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="statusForm.processing">
                                {{ statusForm.processing ? 'Updating...' : 'Update' }}
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update the user's password.</p>
                    </div>

                    <form @submit.prevent="passwordForm.put(`/admin/users/${user.id}`, { onSuccess: () => { passwordForm.reset(); showSuccessToast('Password updated successfully!'); } })" class="space-y-6">
                        <!-- New Password -->
                        <div class="grid gap-2">
                            <Label for="password">New Password</Label>
                            <Input
                                id="password"
                                v-model="passwordForm.password"
                                type="password"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="passwordForm.errors.password" class="mt-1" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="passwordForm.processing">
                                {{ passwordForm.processing ? 'Updating...' : 'Update' }}
                            </Button>
                            <Button 
                                variant="outline" 
                                type="button"
                                @click="passwordForm.reset()"
                            >
                                Clear
                            </Button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>