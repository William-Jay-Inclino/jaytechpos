<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input, InputCurrency } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps<{
    defaultInterestRate: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
    { title: 'Add Customer', href: '/customers/create' },
];

const form = ref({
    name: '',
    mobile_number: '',
    starting_balance: '',
    interest_rate: props.defaultInterestRate?.toString() || '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

async function submit() {
    if (processing.value) return;

    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/customers', {
            ...form.value,
            starting_balance: parseFloat(String(form.value.starting_balance || '0')),
        });

        showSuccessToast(response.data.msg || 'Customer created successfully!');
        router.visit('/customers');
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Head title="Add Customer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-2xl">

                <!-- Form Card -->
                <div class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 sm:p-8 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none">
                    <form @submit.prevent="submit" class="space-y-6">
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
                            <InputError :message="errors.name" class="mt-1" />
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
                            <InputError :message="errors.mobile_number" class="mt-1" />
                        </div>

                        <!-- Starting Balance -->
                        <div class="grid gap-2">
                            <Label for="starting_balance">Starting Balance/Utang</Label>
                            <InputCurrency
                                id="starting_balance"
                                v-model="form.starting_balance"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="errors.starting_balance" class="mt-1" />
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
                                class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <InputError :message="errors.interest_rate" class="mt-1" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Ginagamit ito para sa automatic calculation sa interest every 1st of the month if a customer has outstanding balance.
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="processing">
                                {{ processing ? 'Saving Customer...' : 'Save Customer' }}
                            </Button>
                            <Link href="/customers">
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </AppLayout>
</template>