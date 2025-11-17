<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { Head, useForm, usePage } from '@inertiajs/vue3';


interface User {
    id: number
    name: string
    email: string
    role: string
}

interface Props {
    user: User
}

const props = defineProps<Props>();

const breadcrumbs = [
	{ title: 'Profile', href: '' }
];

// Form for updating user information
const infoForm = useForm({
	name: props.user.name,
	email: props.user.email,
});

// Form for updating password
const passwordForm = useForm({
	current_password: '',
	password: '',
	password_confirmation: '',
});
</script>

<template>
	<Head title="Profile" />
	<AdminLayout :breadcrumbs="breadcrumbs">
		<div class="w-full px-4 py-6 lg:px-8 lg:py-10">
			<div class="mx-auto max-w-2xl space-y-6">
				<!-- User Information Form -->
				<div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
					<div class="mb-6">
						<h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h3>
						<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update your name and email address.</p>
					</div>
					<form @submit.prevent="infoForm.patch('/admin/profile', { onSuccess: () => { showSuccessToast('Profile updated successfully!'); } })" class="space-y-6">
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
								{{ infoForm.processing ? 'Updating...' : 'Update Information' }}
							</Button>
						</div>
					</form>
				</div>
				<!-- Change Password Form -->
				<div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
					<div class="mb-6">
						<h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
						<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update your account password.</p>
					</div>
					<form @submit.prevent="passwordForm.patch('/admin/profile/password', { onSuccess: () => { passwordForm.reset(); showSuccessToast('Password updated successfully!'); } })" class="space-y-6">
						<!-- Current Password -->
						<div class="grid gap-2">
							<Label for="current_password">Current Password</Label>
							<Input
								id="current_password"
								v-model="passwordForm.current_password"
								type="password"
								required
								autocomplete="current-password"
								class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
							/>
							<InputError :message="passwordForm.errors.current_password" class="mt-1" />
						</div>
						<!-- New Password -->
						<div class="grid gap-2">
							<Label for="password">New Password</Label>
							<Input
								id="password"
								v-model="passwordForm.password"
								type="password"
								required
								autocomplete="new-password"
								class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
							/>
							<InputError :message="passwordForm.errors.password" class="mt-1" />
						</div>
						<!-- Confirm Password -->
						<div class="grid gap-2">
							<Label for="password_confirmation">Confirm Password</Label>
							<Input
								id="password_confirmation"
								v-model="passwordForm.password_confirmation"
								type="password"
								required
								autocomplete="new-password"
								class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
							/>
							<InputError :message="passwordForm.errors.password_confirmation" class="mt-1" />
						</div>
						<!-- Actions -->
						<div class="flex items-center gap-4">
							<Button type="submit" :disabled="passwordForm.processing">
								{{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
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
