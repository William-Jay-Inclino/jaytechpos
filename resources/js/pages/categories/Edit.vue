<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Link } from '@inertiajs/vue3'
import { showSuccessToast } from '@/lib/toast';
import { Category } from '@/types/inventory';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categories', href: '/categories' },
    { title: 'Edit Category', href: '/categories/edit' },
];

const props = defineProps<{
    category: Category
}>()

const form = useForm({
    category_name: props.category.category_name,
    description: props.category.description,
});

function submit() {
    form.put(`/categories/${props.category.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessToast('Category updated successfully!');
        },
    });
};
</script>

<template>
    <Head title="Edit Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-lg sm:text-xl lg:text-2xl font-semibold tracking-tight">
                    Edit Category
                </h1>
            </div>

            <!-- Form Card -->
            <div class="rounded-xl border bg-white dark:bg-gray-900 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Category Name -->
                    <div class="grid gap-2">
                        <Label for="category_name">Category Name</Label>
                        <Input
                            id="category_name"
                            v-model="form.category_name"
                            type="text"
                            required
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
                        />
                        <InputError :message="form.errors.category_name" class="mt-1" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Save
                        </Button>
                        <Link href="/categories">
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
