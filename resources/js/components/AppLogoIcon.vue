<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/images/simplepos/simplePOS-logo.png"
        alt="SimplePOS logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
