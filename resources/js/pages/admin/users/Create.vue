<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import { ref } from 'vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { showSuccessToast, showErrorToast } from '@/lib/toast'

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const errors = ref<Record<string, string>>({})
const processing = ref(false)

const submitForm = async () => {
    processing.value = true
    errors.value = {}

    try {
        const res = await axios.post('/admin/users', form.value)
        const data = res?.data || {}

        if (data.success) {
            showSuccessToast(data.msg || 'User created successfully!')
            router.visit('/admin/users')
        } else {
            showErrorToast(data.msg || 'Failed to create user')
        }
    } catch (error: any) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        } else {
            const message = error?.response?.data?.msg || error?.response?.data?.message || 'Failed to create user'
            showErrorToast(message)
        }
    } finally {
        processing.value = false
    }
}
</script>

<template>
    <Head title="Add User" />

    <AdminLayout>
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-2xl">

                <!-- Form Card -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Full Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="errors.name" class="mt-1" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email Address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="errors.email" class="mt-1" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="errors.password" class="mt-1" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="errors.password_confirmation" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="processing">
                                {{ processing ? 'Creating User...' : 'Create User' }}
                            </Button>
                            <Link href="/admin/users">
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </AdminLayout>
</template>