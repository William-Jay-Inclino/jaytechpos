<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

const props = defineProps<{
    categories: Array<any>,
}>()


</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <Link href="/categories/create">
                <Button class="mb-4">Add Category</Button>
            </Link>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead> Name </TableHead>
                        <TableHead> Description </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="category in categories" :key="category.id">
                        <TableCell class="font-medium"> {{ category.category_name }} </TableCell>
                        <TableCell class="font-medium"> {{ category.description }} </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AppLayout>
</template>
