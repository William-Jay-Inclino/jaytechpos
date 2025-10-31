<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { showSuccessToast } from '@/lib/toast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Product Categories', href: '/product-categories' },
    { title: 'Create Product Category', href: '/product-categories/create' },
];

const form = useForm({
    name: '',
    description: '',
});

function submit() {
    form.post('/product-categories', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showSuccessToast('Category created successfully!');
        },
    });
}
</script>

<template>
    <Head title="Create Product Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1
                    class="text-lg font-semibold tracking-tight sm:text-xl lg:text-2xl"
                >
                    Create Product Category
                </h1>
            </div>

            <!-- Form Card -->
            <div
                class="rounded-xl border bg-white p-6 shadow-sm sm:p-8 dark:border-gray-700 dark:bg-gray-900"
            >
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Category Name -->
                    <div class="grid gap-2">
                        <Label for="category_name">Product Category Name</Label>
                        <Input
                            id="category_name"
                            v-model="form.name"
                            type="text"
                            required
                            class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                        <InputError
                            :message="form.errors.description"
                            class="mt-1"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Save
                        </Button>
                        <Link href="/product-categories">
                            <Button variant="outline" type="button">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
