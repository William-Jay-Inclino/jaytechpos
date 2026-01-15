<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { showErrorToast } from '@/lib/toast';
import axios from 'axios';
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

interface StockMovement {
    id: number;
    product_id: number;
    type: string;
    type_label: string;
    quantity: string;
    reference: string | null;
    remarks: string | null;
    created_at: string;
    updated_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    open: boolean;
    productId: number | null;
    productName?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const movements = ref<StockMovement[]>([]);
const pagination = ref<Pagination | null>(null);
const loading = ref(false);
const currentPage = ref(1);

async function fetchMovements(page: number = 1) {
    if (!props.productId) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/stock-movements/product/${props.productId}?page=${page}`);
        
        if (response.data.success) {
            movements.value = response.data.data;
            pagination.value = response.data.pagination;
            currentPage.value = page;
        }
    } catch (error: any) {
        showErrorToast(error.response?.data?.message || 'Failed to load stock movements');
    } finally {
        loading.value = false;
    }
}

function handleClose() {
    emit('update:open', false);
}

function goToPage(page: number) {
    if (page >= 1 && pagination.value && page <= pagination.value.last_page) {
        fetchMovements(page);
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString();
}

function getTypeColor(type: string): string {
    switch (type) {
        case 'IN':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'OUT':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'ADJUSTMENT':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
    }
}

watch(() => props.open, (newVal) => {
    if (newVal && props.productId) {
        fetchMovements(1);
    }
});

onMounted(() => {
    if (props.open && props.productId) {
        fetchMovements(1);
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-[95vw] lg:max-w-[90vw] xl:max-w-7xl max-h-[85vh] overflow-hidden flex flex-col gap-0 p-0 sm:top-[5%] sm:translate-y-0">
            <DialogHeader class="px-6 pt-6 pb-4 border-b">
                <DialogTitle class="text-2xl font-semibold">Stock Movement History</DialogTitle>
                <DialogDescription v-if="productName" class="text-base mt-1">
                    {{ productName }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div v-if="loading" class="flex items-center justify-center py-16">
                    <Loader2 class="h-10 w-10 animate-spin text-primary" />
                </div>

                <div v-else-if="movements.length === 0" class="py-16 text-center">
                    <p class="text-gray-500 text-base">No transaction records found</p>
                </div>

                <div v-else class="overflow-x-auto lg:overflow-x-visible -mx-6 px-6">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="h-12 text-xs uppercase tracking-wide font-semibold w-32">Type</TableHead>
                                <TableHead class="h-12 text-xs uppercase tracking-wide font-semibold text-right w-32">Quantity</TableHead>
                                <TableHead class="h-12 text-xs uppercase tracking-wide font-semibold w-40">Reference</TableHead>
                                <TableHead class="h-12 text-xs uppercase tracking-wide font-semibold">Remarks</TableHead>
                                <TableHead class="h-12 text-xs uppercase tracking-wide font-semibold w-52">Date & Time</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="movement in movements" :key="movement.id" class="border-b last:border-0">
                                <TableCell class="py-4">
                                    <span 
                                        :class="getTypeColor(movement.type)" 
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold"
                                    >
                                        {{ movement.type_label }}
                                    </span>
                                </TableCell>
                                <TableCell class="py-4 font-semibold text-right tabular-nums">
                                    {{ movement.quantity }}
                                </TableCell>
                                <TableCell class="py-4">
                                    <span class="text-sm">{{ movement.reference || '—' }}</span>
                                </TableCell>
                                <TableCell class="py-4 max-w-xs">
                                    <span class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ movement.remarks || '—' }}
                                    </span>
                                </TableCell>
                                <TableCell class="py-4">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDate(movement.created_at) }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination && pagination.last_page > 1"
                class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t px-6 py-4 bg-gray-50/50 dark:bg-gray-900/50"
            >
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing <span class="font-medium text-gray-900 dark:text-gray-100">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span> to 
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span> of 
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ pagination.total }}</span> entries
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentPage === 1 || loading"
                        @click="goToPage(currentPage - 1)"
                        class="h-9"
                    >
                        <ChevronLeft class="h-4 w-4 mr-1" />
                        Previous
                    </Button>
                    <div class="text-sm font-medium px-3">
                        {{ currentPage }} / {{ pagination.last_page }}
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentPage === pagination.last_page || loading"
                        @click="goToPage(currentPage + 1)"
                        class="h-9"
                    >
                        Next
                        <ChevronRight class="h-4 w-4 ml-1" />
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
