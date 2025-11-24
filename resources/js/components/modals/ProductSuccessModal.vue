<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { CheckCircle, Package } from 'lucide-vue-next';
import { computed } from 'vue';
import { formatCurrency } from '@/utils/currency';

interface Product {
    id: number;
    product_name: string;
    unit_price: number;
    cost_price: number;
    status: string;
    unit?: {
        unit_name: string;
        abbreviation: string;
    };
}

const props = defineProps<{
    open: boolean;
    product: Product | null;
    mode?: 'create' | 'edit';
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEditMode = computed(() => props.mode === 'edit');

const modalTitle = computed(() => {
    return isEditMode.value 
        ? 'Product Updated Successfully!' 
        : 'Product Created Successfully!';
});

const buttonText = computed(() => {
    return isEditMode.value 
        ? 'Back to Products' 
        : 'Create Another Product';
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[85vh] max-w-md overflow-y-auto">
            <DialogHeader class="pb-4 text-center">
                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                    <CheckCircle class="h-8 w-8 text-green-600 dark:text-green-400" />
                </div>
                <DialogTitle class="text-xl text-green-700 dark:text-green-400">
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    <!-- {{ modalDescription }} -->
                </DialogDescription>
            </DialogHeader>

            <div v-if="product" class="space-y-4">
                <!-- Product Card -->
                <div class="space-y-4 rounded-lg bg-muted/30 p-4">
                    <!-- Product Name & Status -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Package class="h-5 w-5 text-muted-foreground" />
                            <h3 class="font-semibold">{{ product.product_name }}</h3>
                        </div>
                        <!-- <Badge :variant="statusVariant">
                            {{ product.status === 'active' ? 'Active' : 'Inactive' }}
                        </Badge> -->
                    </div>

                    <!-- Unit -->
                    <!-- <div class="text-sm">
                        <span class="text-muted-foreground">Unit:</span>
                        <div class="font-medium">
                            {{ product.unit?.unit_name }} ({{ product.unit?.abbreviation }})
                        </div>
                    </div> -->

                    <!-- Pricing -->
                    <div class="grid grid-cols-2 gap-4 border-t pt-3">
                        <div class="text-center">
                            <div class="text-sm text-muted-foreground">Selling Price</div>
                            <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency(product.unit_price) }}
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-sm text-muted-foreground">Cost Price</div>
                            <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                {{ formatCurrency(product.cost_price) }}
                            </div>
                        </div>
                    </div>

                    <!-- Profit Margin -->
                    <div class="rounded-md bg-blue-50 p-3 text-center dark:bg-blue-950/20">
                        <div class="text-sm text-blue-600 dark:text-blue-400">Potential Profit</div>
                        <div class="text-lg font-bold text-blue-700 dark:text-blue-300">
                            {{ formatCurrency(product.unit_price - product.cost_price) }}
                        </div>
                    </div>
                </div>

                <!-- Success Message -->
                <div :class="product.status === 'active' ? 'bg-green-50 dark:bg-green-950/20' : 'bg-orange-50 dark:bg-orange-950/20'" class="rounded-lg p-3 text-center">
                    <p :class="product.status === 'active' ? 'text-green-700 dark:text-green-300' : 'text-orange-700 dark:text-orange-300'" class="text-sm">
                        <template v-if="product.status === 'active'">
                            Product is now available for sale transactions!
                        </template>
                        <template v-else>
                            Product is saved but not available for sale (inactive status).
                        </template>
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-center pt-4">
                <Button @click="isOpen = false" class="w-full">
                    {{ buttonText }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>