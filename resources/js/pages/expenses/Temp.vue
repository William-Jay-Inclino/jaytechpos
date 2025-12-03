<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <Head title="Test Expenses" />
    
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md">
            <h1 class="text-2xl font-bold text-green-600 mb-4">✓ Expenses Route Working!</h1>
            <p class="text-gray-700">{{ message || 'If you see this, the issue is specifically with expenses/Index.vue' }}</p>
            <p class="text-sm text-gray-500 mt-4">This is a temporary test page at expenses/Temp.vue</p>
        </div>
    </div>
</template>
