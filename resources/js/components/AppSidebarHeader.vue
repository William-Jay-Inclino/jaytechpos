<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Sun, Moon } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();

</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- LEFT SIDE: sidebar + breadcrumbs -->
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />

            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- RIGHT SIDE: theme toggle -->
        <div class="ml-auto">
            <button
                @click="updateAppearance(appearance === 'dark' ? 'light' : 'dark')"
                type="button"
                class="p-1 rounded border border-transparent hover:bg-gray-100 dark:hover:bg-gray-700"
                aria-label="Toggle theme"
            >
                <Sun v-if="appearance === 'dark'" class="h-4 w-4" />
                <Moon v-else class="h-4 w-4" />
            </button>
        </div>
    </header>
</template>
