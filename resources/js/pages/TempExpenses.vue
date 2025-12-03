<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    message: string;
}>();
</script>

<template>
    <Head title="Test Expenses" />
    
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-2xl font-bold text-green-600 mb-4">✓ Route Working!</h1>
            <p class="text-gray-700">{{ message }}</p>
        </div>
    </div>
</template>
