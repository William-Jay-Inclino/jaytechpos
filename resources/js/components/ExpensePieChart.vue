<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    type ChartOptions,
    type ChartData,
} from 'chart.js';
import { Pie } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

interface ChartDataItem {
    id?: number;
    label: string;
    amount: number;
    count: number;
    color: string;
}

const props = defineProps<{
    data: ChartDataItem[];
}>();

const emit = defineEmits<{
    (e: 'category-click', category: ChartDataItem): void;
}>();

// Reactive theme detection
const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const chartData = computed<ChartData<'pie'>>(() => {
    const colors = props.data.map(item => item.color);
    
    return {
        labels: props.data.map(item => item.label),
        datasets: [
            {
                data: props.data.map(item => item.amount),
                backgroundColor: colors,
                borderColor: colors,
                borderWidth: 2,
            },
        ],
    };
});

const chartOptions = computed<ChartOptions<'pie'>>(() => {
    const textColor = isDarkMode.value ? '#FFFFFF' : '#374151';
    
    return {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const category = props.data[index];
                emit('category-click', category);
            }
        },
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    color: textColor,
                    font: {
                        size: 12,
                    },
                    generateLabels: (chart) => {
                        const datasets = chart.data.datasets;
                        if (datasets.length > 0 && datasets[0].data.length > 0) {
                            return props.data.map((item, index) => {
                                const backgroundColor = chartData.value.datasets[0].backgroundColor;
                                const color = Array.isArray(backgroundColor) ? backgroundColor[index] : '#000';
                                
                                return {
                                    text: `${item.label}: ₱${item.amount.toLocaleString()}`,
                                    fillStyle: color,
                                    strokeStyle: color,
                                    fontColor: textColor,
                                    lineWidth: 0,
                                    pointStyle: 'circle',
                                    hidden: false,
                                    index: index,
                                };
                            });
                        }
                        return [];
                    },
                },
                onClick: (event, legendItem, legend) => {
                    const index = legendItem.index;
                    if (index !== undefined) {
                        const category = props.data[index];
                        emit('category-click', category);
                    }
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const item = props.data[context.dataIndex];
                        return `${item.label}: ₱${item.amount.toLocaleString()} (${item.count} expense${item.count !== 1 ? 's' : ''})`;
                    },
                    footer: () => {
                        return 'Click to view details';
                    },
                },
            },
        },
    };
});

const hasData = computed(() => props.data.length > 0 && props.data.some(item => item.amount > 0));

// Watch for theme changes
onMounted(() => {
    const observer = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark');
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
});
</script>

<template>
    <div class="w-full">
        <div v-if="!hasData" class="flex h-64 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <span class="text-2xl">📊</span>
                </div>
                <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No expense data</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Add some expenses to see the chart
                </p>
            </div>
        </div>
        
        <div v-else class="h-64 cursor-pointer">
            <Pie :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>