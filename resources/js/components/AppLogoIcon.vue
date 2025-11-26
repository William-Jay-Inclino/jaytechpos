<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

interface Props {
    className?: HTMLAttributes['class'];
    /** size in px (applies to both width and height) */
    size?: number | string;
    /** image source, defaults to project logo */
    src?: string;
    /** alt text for accessibility */
    alt?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 56,
    src: '/logo.png',
    alt: 'JayTech logo',
});
</script>

<template>
    <div
        :class="[
            'inline-block rounded-xl overflow-hidden p-1 bg-gradient-to-br from-indigo-50 to-white shadow-lg transition-transform duration-200 hover:scale-105',
            props.className
        ]"
        role="img"
        :aria-label="props.alt"
    >
        <img
            :src="props.src"
            :alt="props.alt"
            :width="props.size"
            :height="props.size"
            v-bind="$attrs"
            style="object-fit:contain; display:block;"
        />
    </div>
</template>
