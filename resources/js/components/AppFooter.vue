<script setup lang="ts">
import { Phone, MessageCircle } from 'lucide-vue-next';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="mt-auto border-t border-neutral-200 bg-white/50 px-4 py-3 text-center text-[10px] text-neutral-600 backdrop-blur-sm dark:border-neutral-800 dark:bg-neutral-950/50 dark:text-neutral-400">
        <p>
            Contact the developer if you have any questions or suggestions
        </p>
        <p class="mt-1 flex items-center justify-center gap-2">
            <span class="flex items-center gap-1">
                <Phone class="h-3 w-3" />
                <span class="font-bold">0910-602-4370</span>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1">
                <MessageCircle class="h-3 w-3" />
                <span class="font-bold">Jay Inclino</span>
            </span>
        </p>
    </footer>

</template>
