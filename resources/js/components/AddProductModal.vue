<script lang="ts" setup>
import { ref, watch, computed, nextTick } from 'vue';
import type { Product } from '../types/inventory';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { X } from 'lucide-vue-next';

const props = defineProps<{ 
    open: boolean;
    products: Product[];
}>();
const emit = defineEmits(['close', 'add']);

const search = ref('');
const selected = ref<Product | null>(null);
const quantity = ref(1);
const searchInput = ref<any>(null);

// Simplified search logic using computed property
const filteredProducts = computed(() => {
    if (!search.value.trim()) {
        return [];
    }
    
    const searchTerm = search.value.toLowerCase().trim();
    return props.products.filter(product =>
        product.product_name.toLowerCase().includes(searchTerm) ||
        product.sku.toLowerCase().includes(searchTerm) ||
        (product.barcode && product.barcode.toLowerCase().includes(searchTerm))
    );
});

function selectProduct(product: Product) {
    selected.value = product;
}

function handleAddItem() {
    if (selected.value) {
        emit('add', { product: selected.value, quantity: quantity.value });
    }
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && selected.value) {
        event.preventDefault();
        handleAddItem();
    }
}

// Auto-select product when only one result is found
watch(filteredProducts, (products) => {
    if (products.length === 1) {
        selected.value = products[0];
    } else if (products.length === 0) {
        selected.value = null;
    }
});

watch(() => props.open, async (val) => {
    if (!val) {
        search.value = '';
        selected.value = null;
        quantity.value = 1;
    } else {
        // Only try to focus if modal is actually open and DOM is ready
        await nextTick();
        // Add a small delay to ensure DOM is fully rendered
        setTimeout(() => {
            try {
                if (!searchInput.value) return;
                
                // Try different ways to access the input element
                let inputElement = null;
                
                // Method 1: Direct access if it's already an input element
                if (typeof searchInput.value.focus === 'function') {
                    inputElement = searchInput.value;
                }
                // Method 2: Check if it has $el property (Vue component)
                else if (searchInput.value.$el) {
                    if (searchInput.value.$el.tagName === 'INPUT') {
                        inputElement = searchInput.value.$el;
                    } else {
                        inputElement = searchInput.value.$el.querySelector('input');
                    }
                }
                // Method 3: If it's a wrapper, look for input inside
                else if (typeof searchInput.value.querySelector === 'function') {
                    inputElement = searchInput.value.querySelector('input');
                }
                
                if (inputElement && typeof inputElement.focus === 'function') {
                    inputElement.focus();
                    if (typeof inputElement.select === 'function') {
                        inputElement.select();
                    }
                }
            } catch (error) {
                // Silently handle the error to avoid console warnings
            }
        }, 50); // Small delay to ensure DOM is ready
    }
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 bg-black/40">
        <div class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-900 rounded-lg shadow-lg w-[600px] max-h-[80vh] overflow-hidden" @keydown="handleKeydown">
            <div class="p-6 pb-4 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Add Product</h2>
                    <Button variant="ghost" size="icon" @click="$emit('close')">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="space-y-2">
                    <Label for="search">Search Product</Label>
                    <Input
                        id="search"
                        ref="searchInput"
                        v-model="search"
                        type="text"
                        placeholder="Search by name, SKU, or barcode"
                        autocomplete="off"
                        @keydown="handleKeydown"
                    />
                </div>

                <div v-if="search && !filteredProducts.length" class="flex items-center justify-center py-8 text-muted-foreground">
                    <p>No products found matching "{{ search }}"</p>
                </div>

                <div v-if="filteredProducts.length" class="space-y-2">
                    <Label>Search Results ({{ filteredProducts.length }})</Label>
                    <div class="max-h-40 overflow-y-auto border rounded-md">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            @click="selectProduct(product)"
                            class="cursor-pointer p-3 hover:bg-muted border-b last:border-b-0 transition-colors"
                            :class="{ 'bg-muted': selected?.id === product.id }"
                        >
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <p class="font-medium">{{ product.product_name }}</p>
                                    <p class="text-sm text-muted-foreground">SKU: {{ product.sku }}</p>
                                    <p v-if="product.barcode" class="text-sm text-muted-foreground">
                                        Barcode: {{ product.barcode }}
                                    </p>
                                </div>
                                <Badge variant="outline">₱{{ product.unit_price }}</Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selected" class="space-y-4 border-t pt-4">
                    <Label>Product Details</Label>
                    <Card>
                        <CardContent class="p-4 space-y-3">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Name:</span>
                                    <p class="font-medium">{{ selected.product_name }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">SKU:</span>
                                    <p class="font-medium">{{ selected.sku }}</p>
                                </div>
                                <div v-if="selected.barcode">
                                    <span class="text-muted-foreground">Barcode:</span>
                                    <p class="font-medium">{{ selected.barcode }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Category:</span>
                                    <p class="font-medium">{{ selected.category?.category_name || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Supplier:</span>
                                    <p class="font-medium">{{ selected.supplier?.supplier_name || '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Unit Price:</span>
                                    <p class="font-medium">₱{{ selected.unit_price }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Status:</span>
                                    <Badge :variant="selected.status === 'active' ? 'default' : 'secondary'">
                                        {{ selected.status }}
                                    </Badge>
                                </div>
                            </div>
                            
                            <!-- Quantity Section -->
                            <div class="border-t pt-3">
                                <div class="space-y-2">
                                    <Label for="quantity">Quantity</Label>
                                    <Input
                                        id="quantity"
                                        v-model.number="quantity"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="w-24"
                                        @keydown="handleKeydown"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="p-6 pt-4 border-t bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div v-if="selected" class="text-sm text-muted-foreground">
                        Selected: {{ selected.product_name }}
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        Select a product to continue
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="$emit('close')">
                            Cancel
                        </Button>
                        <Button 
                            :disabled="!selected" 
                            @click="handleAddItem"
                        >
                            Add Item
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>



<style scoped>
@media (max-width: 640px) {
    .w-\[600px\] {
        width: calc(100vw - 2rem);
        max-width: 600px;
    }
}
</style>
