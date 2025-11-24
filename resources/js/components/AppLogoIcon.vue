<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

// Allow standard attributes (class, style, width, height) to be passed through to the root <img>
// and ensure the image preserves its aspect ratio when consumers set both width and height.
defineOptions({});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/jt_logo.png"
        alt="JayTech logo"
        :class="className"
        v-bind="$attrs"
        style="object-fit:contain;"
    />
</template>
