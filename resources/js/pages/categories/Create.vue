<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categories', href: '/categories' },
    { title: 'Create Category', href: '/categories/create' },
];

// Inertia form helper
const form = useForm({
    category_name: '',
    description: '',
});

function submit() {
    form.post('/categories', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

</script>

<template>
    <Head title="Create Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <form @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="category_name">Category Name</Label>
                    <Input id="category_name" class="mt-1 block w-full" v-model="form.category_name" required/>
                    <InputError :message="form.errors.category_name" class="mt-2" />
                </div>

                <div class="grid gap-2 mt-4">
                    <Label for="description">Description</Label>
                    <Textarea id="description" class="mt-1 block w-full" v-model="form.description" />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div class="flex items-center gap-4 mt-6">
                    <Button type="submit" :disabled="form.processing">
                        Submit
                    </Button>
                </div>

            </form>
        </div>

    </AppLayout>
</template>
