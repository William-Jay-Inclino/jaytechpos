<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelMonth: number;
    modelYear: number;
}>();

const emit = defineEmits<{
    'update:modelMonth': [value: number];
    'update:modelYear': [value: number];
}>();

const localMonth = ref(props.modelMonth);
const localYear = ref(props.modelYear);

const monthOptions = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const currentMonthYear = computed(() => {
    const month = monthOptions.find(m => m.value === localMonth.value);
    return `${month?.label} ${localYear.value}`;
});

function goToPreviousMonth() {
    if (localMonth.value === 1) {
        localMonth.value = 12;
        localYear.value--;
    } else {
        localMonth.value--;
    }
    emitChanges();
}

function goToNextMonth() {
    if (localMonth.value === 12) {
        localMonth.value = 1;
        localYear.value++;
    } else {
        localMonth.value++;
    }
    emitChanges();
}

function emitChanges() {
    emit('update:modelMonth', localMonth.value);
    emit('update:modelYear', localYear.value);
}
</script>

<template>
    <div class="flex items-center justify-center gap-2">
        <Button
            variant="ghost"
            size="sm"
            @click="goToPreviousMonth"
            class="h-8 w-8 p-0"
        >
            <ChevronLeft class="h-4 w-4" />
        </Button>
        
        <div class="min-w-[140px] text-center font-medium text-gray-900 dark:text-white">
            {{ currentMonthYear }}
        </div>
        
        <Button
            variant="ghost"
            size="sm"
            @click="goToNextMonth"
            class="h-8 w-8 p-0"
        >
            <ChevronRight class="h-4 w-4" />
        </Button>
    </div>
</template>
