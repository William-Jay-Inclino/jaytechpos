<script setup lang="ts">
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="mt-auto border-t border-neutral-200 bg-white/50 px-4 py-3 text-center text-xs text-neutral-600 backdrop-blur-sm dark:border-neutral-800 dark:bg-neutral-950/50 dark:text-neutral-400">
        <p>
            Developed by <span class="font-semibold">William Jay Inclino</span> © {{ currentYear }}
        </p>
        <p class="mt-1 space-x-2">
            <span>Contact Number:</span>
            <span class="text-amber-600">0910-602-4370</span>
            <span>•</span>
            <span>Messenger:</span>
            <span class="text-amber-600">Jay Inclino</span>
        </p>
    </footer>
</template>
