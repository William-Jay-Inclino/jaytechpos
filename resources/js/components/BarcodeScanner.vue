<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

const emit = defineEmits<{
    (e: 'scanned', barcode: string): void;
}>();

/**
 * HID barcode scanner detection.
 *
 * USB/Bluetooth barcode scanners emulate keyboard input by typing characters
 * rapidly and ending with Enter. We detect this pattern by measuring the time
 * between keystrokes—anything faster than SCAN_THRESHOLD_MS is treated as
 * scanner input rather than manual typing.
 */
let buffer = '';
let lastKeyTime = 0;
const SCAN_THRESHOLD_MS = 50;
const MIN_BARCODE_LENGTH = 4;

function handleKeydown(event: KeyboardEvent): void {
    // Skip if the user is typing in an input, textarea, or contenteditable element
    const target = event.target as HTMLElement;
    const isTypingInField =
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.isContentEditable;

    const now = Date.now();

    if (now - lastKeyTime > SCAN_THRESHOLD_MS) {
        buffer = '';
    }

    lastKeyTime = now;

    if (event.key === 'Enter') {
        if (buffer.length >= MIN_BARCODE_LENGTH) {
            event.preventDefault();
            event.stopPropagation();
            emit('scanned', buffer.trim());
        }
        buffer = '';
        return;
    }

    // Only accumulate single-character keys (ignore Shift, Ctrl, etc.)
    if (event.key.length === 1 && !isTypingInField) {
        buffer += event.key;
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown, true);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown, true);
});
</script>

<template>
    <!-- Renderless component: listens for HID barcode scanner input globally -->
</template>
