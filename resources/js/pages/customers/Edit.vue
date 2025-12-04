<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { showErrorToast, showSuccessToast } from '@/lib/toast';
import { showConfirmDelete } from '@/lib/swal';
import { Customer, type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import EditBalanceModal from '@/components/modals/EditBalanceModal.vue';
import { Edit3 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    customer: Customer;
    defaultInterestRate: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
    { title: `Edit ${props.customer.name}`, href: `/customers/${props.customer.id}/edit` },
];

const form = reactive({
    name: props.customer.name || '',
    mobile_number: props.customer.mobile_number || '',
    interest_rate: props.customer.interest_rate?.toString() || '',
    processing: false,
    errors: {} as Record<string, string>
});

// Modal state
const showBalanceModal = ref(false);

const handleDelete = async () => {
    const result = await showConfirmDelete({
        title: 'Delete Customer',
        text: `Are you sure you want to delete ${props.customer.name}? This action cannot be undone.`,
        confirmButtonText: 'Yes, delete customer!',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel',
        position: 'center'
    });

    if (result.isConfirmed) {
        try {
            const res = await axios.delete(`/customers/${props.customer.id}`)

            const data = res?.data || {}

            if (data.success) {
                showSuccessToast(data.msg || 'Customer deleted successfully!')

                // SPA redirect to customer index
                router.visit('/customers')

            } else {
                // backend responded but indicated failure
                showErrorToast(data.msg || 'Failed to delete customer')
            }
        } catch (error: any) {
            // try to show a useful message from the response when available
            const message = error?.response?.data?.msg || error?.response?.data?.message || 'Failed to delete customer'
            showErrorToast(message)
        }
    }
};

const handleBalanceSuccess = async () => {
    // Fetch updated customer data
    try {
        const res = await axios.get(`/customers/${props.customer.id}/edit`);
        const data = res?.data;
        
        if (data?.customer) {
            // Update local customer data
            Object.assign(props.customer, data.customer);
        }
    } catch (error) {
        // Silently fail, user can refresh if needed
        console.error('Failed to refresh customer data:', error);
    }
};

const handleSubmit = async () => {
    if (form.processing) return;

    form.processing = true;
    form.errors = {};

    try {
        const response = await axios.put(`/customers/${props.customer.id}`, {
            name: form.name,
            mobile_number: form.mobile_number,
            interest_rate: form.interest_rate,
        });

        showSuccessToast(response.data.msg || 'Customer updated successfully!');
        router.visit('/customers');
    } catch (error: any) {
        if (error.response?.status === 422) {
            form.errors = error.response.data.errors || {};
            const firstError = Object.values(form.errors)[0];
            showErrorToast(Array.isArray(firstError) ? firstError[0] : firstError);
        } else {
            showErrorToast('Failed to update customer');
        }
    } finally {
        form.processing = false;
    }
};
</script>

<template>
    <Head :title="`Edit ${customer.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-2xl">
                <!-- Page Header -->
                <div class="mb-8 flex items-center justify-between">

                    <Button
                        variant="destructive"
                        size="sm"
                        @click="handleDelete"
                        class="font-semibold"
                    >
                        Delete
                    </Button>
                </div>

                <!-- Form Card -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Customer Name -->
                        <div class="grid gap-2">
                            <Label for="name">Customer Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="form.errors.name?.[0] || form.errors.name" class="mt-1" />
                        </div>

                        <!-- Mobile Number -->
                        <div class="grid gap-2">
                            <Label for="mobile_number">
                                Mobile Number <span class="text-sm text-gray-400 dark:text-gray-400">(Optional)</span>
                            </Label>
                            <Input
                                id="mobile_number"
                                v-model="form.mobile_number"
                                type="tel"
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="form.errors.mobile_number?.[0] || form.errors.mobile_number" class="mt-1" />
                        </div>

                        <!-- Interest Rate -->
                        <div class="grid gap-2">
                            <Label for="interest_rate">Interest Rate (%)</Label>
                            <Input
                                id="interest_rate"
                                v-model="form.interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                :placeholder="`Default: ${defaultInterestRate}%`"
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="form.errors.interest_rate?.[0] || form.errors.interest_rate" class="mt-1" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving Customer...' : 'Save Customer' }}
                            </Button>
                            <Link href="/customers">
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>

                <div
                    class="mt-6 rounded-xl border border-gray-300 bg-gray-50 p-6 shadow-sm ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-900/50 dark:ring-gray-800 dark:shadow-none"
                >
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Utang Details
                    </h3>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Current Balance
                                </p>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="showBalanceModal = true"
                                    class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    title="Edit balance"
                                >
                                    <Edit3 class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                </Button>
                            </div>
                            <p 
                                :class="[
                                    'text-2xl font-bold',
                                    customer.running_utang_balance > 0 
                                        ? 'text-red-600 dark:text-red-400' 
                                        : 'text-green-600 dark:text-green-400'
                                ]"
                            >
                                ₱{{ customer.running_utang_balance?.toLocaleString() || '0.00' }}
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Effective Interest Rate
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ customer.effective_interest_rate?.toFixed(2) || '0.00' }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Balance Modal -->
        <EditBalanceModal
            v-model:open="showBalanceModal"
            :customer-id="customer.id"
            :customer-name="customer.name"
            :current-balance="customer.running_utang_balance || 0"
            @success="handleBalanceSuccess"
        />
    </AppLayout>
</template>