<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { showErrorToast, showSuccessToast } from '@/lib/toast';
import { showConfirmDelete } from '@/lib/swal';
import { Customer, type BreadcrumbItem } from '@/types';
import { Form, Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    customer: Customer;
    defaultInterestRate: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
    {
        title: `Edit ${props.customer.name}`,
        href: `/customers/${props.customer.id}/edit`,
    },
];

// Form state
const name = ref<string>('');
const mobileNumber = ref<string>('');
const remarks = ref<string>('');
const interestRate = ref<string>('');

// Initialize form with customer data
onMounted(() => {
    name.value = props.customer.name;
    mobileNumber.value = props.customer.mobile_number || '';
    remarks.value = props.customer.remarks || '';
    interestRate.value = props.customer.interest_rate?.toString() || '';
});

const handleFormSuccess = () => {
    showSuccessToast('Customer updated successfully!');
};

const handleFormError = () => {
    showErrorToast('Failed to update customer. Please try again.');
};

const handleDelete = async () => {
    const result = await showConfirmDelete({
        title: 'Delete Customer',
        text: `Are you sure you want to delete ${props.customer.name}? This action cannot be undone and will remove all associated data.`,
        confirmButtonText: 'Yes, delete customer!',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel',
        position: 'center'
    });

    if (result.isConfirmed) {
        // Use Inertia router to make DELETE request
        router.delete(`/customers/${props.customer.id}`, {
            onSuccess: () => {
                showSuccessToast('Customer deleted successfully!');
            },
            onError: (errors: any) => {
                if (errors && Object.keys(errors).length > 0) {
                    showErrorToast('Cannot delete customer with existing records.');
                } else {
                    showErrorToast('Failed to delete customer. Please try again.');
                }
            }
        });
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
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white"
                        >
                            ✏️ Edit Customer
                        </h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Update {{ customer.name }}'s information
                        </p>
                    </div>

                    <Button
                        variant="destructive"
                        size="sm"
                        @click="handleDelete"
                        class="font-semibold"
                    >
                        🗑️ Delete
                    </Button>
                </div>

                <!-- Edit Form Card -->
                <div
                    class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                >
                    <Form
                        :action="`/customers/${customer.id}`"
                        method="put"
                        v-slot="{ errors, processing }"
                        @success="handleFormSuccess"
                        @error="handleFormError"
                        class="space-y-6"
                    >
                        <!-- Customer Name -->
                        <div class="space-y-3">
                            <Label
                                for="name"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Customer Name
                                <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                placeholder="Enter customer name"
                                v-model="name"
                                maxlength="255"
                                required
                                class="h-12 border-2 focus:ring-2 focus:ring-blue-500"
                            />
                            <div
                                v-if="errors.name"
                                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                            >
                                {{ errors.name }}
                            </div>
                        </div>

                        <!-- Mobile Number -->
                        <div class="space-y-3">
                            <Label
                                for="mobile_number"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Mobile Number
                                <span class="text-gray-500">(Optional)</span>
                            </Label>
                            <Input
                                id="mobile_number"
                                name="mobile_number"
                                type="tel"
                                placeholder="Enter mobile number"
                                v-model="mobileNumber"
                                maxlength="20"
                                class="h-12 border-2 focus:ring-2 focus:ring-blue-500"
                            />
                            <div
                                v-if="errors.mobile_number"
                                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                            >
                                {{ errors.mobile_number }}
                            </div>
                        </div>

                        <!-- Interest Rate -->
                        <div class="space-y-3">
                            <Label
                                for="interest_rate"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Interest Rate (%)
                                <span class="text-gray-500">(Default: {{ defaultInterestRate }}%)</span>
                            </Label>
                            <Input
                                id="interest_rate"
                                name="interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                :placeholder="`Default: ${defaultInterestRate}%`"
                                v-model="interestRate"
                                class="h-12 border-2 text-right focus:ring-2 focus:ring-blue-500"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                This will be used for calculating utang interest. Current value: {{ interestRate || defaultInterestRate }}%
                            </p>
                            <div
                                v-if="errors.interest_rate"
                                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                            >
                                {{ errors.interest_rate }}
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="space-y-3">
                            <Label
                                for="remarks"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Remarks
                                <span class="text-gray-500">(Optional)</span>
                            </Label>
                            <Textarea
                                id="remarks"
                                name="remarks"
                                placeholder="Additional notes about this customer..."
                                rows="4"
                                maxlength="1000"
                                v-model="remarks"
                                class="border-2 focus:ring-2 focus:ring-blue-500"
                            />
                            <div
                                v-if="errors.remarks"
                                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                            >
                                {{ errors.remarks }}
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4">
                            <Button
                                type="submit"
                                :disabled="processing || !name"
                                class="flex-1 h-12 bg-green-600 text-lg font-semibold text-white shadow-lg hover:bg-green-700"
                            >
                                <span v-if="processing" class="flex items-center gap-2">
                                    <div
                                        class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                                    ></div>
                                    Updating...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    ✏️ Update Customer
                                </span>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                @click="router.visit('/customers')"
                                class="h-12 px-6 font-semibold"
                            >
                                ❌ Cancel
                            </Button>
                        </div>
                    </Form>
                </div>

                <!-- Customer Information -->
                <div
                    class="mt-6 rounded-xl border border-gray-300 bg-gray-50 p-6 shadow-sm ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-900/50 dark:ring-gray-800 dark:shadow-none"
                >
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        📊 Customer Information
                    </h3>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Current Balance
                            </p>
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
    </AppLayout>
</template>