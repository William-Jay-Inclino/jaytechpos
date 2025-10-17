<script lang="ts" setup>
import { ref, watch, computed, nextTick } from 'vue';
import type { Product } from '@/types/pos';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { X } from 'lucide-vue-next';

// Types
interface AddProductData {
    product: Product;
    quantity: number;
}

// Props & Emits
interface Props {
    open: boolean;
    products: Product[];
}

interface Emits {
    close: [];
    add: [data: AddProductData];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Reactive State
const searchQuery = ref<string>('');
const selectedProduct = ref<Product | null>(null);
const productQuantity = ref<number>(1);
const searchInputRef = ref<any>(null);

// Computed Properties
const searchResults = computed((): Product[] => {
    const query = searchQuery.value.trim();
    if (!query) return [];
    
    const searchTerm = query.toLowerCase();
    return props.products.filter((product: Product) => {
        return product.product_name.toLowerCase().includes(searchTerm);
    });
});

const hasSearchResults = computed((): boolean => searchResults.value.length > 0);
const hasSearchQuery = computed((): boolean => searchQuery.value.trim().length > 0);
const isProductSelected = computed((): boolean => selectedProduct.value !== null);

// Business Logic Functions
function selectProduct(product: Product): void {
    selectedProduct.value = product;
}

function addProductToCart(): void {
    if (!selectedProduct.value) return;
    
    emit('add', { 
        product: selectedProduct.value, 
        quantity: productQuantity.value 
    });
}

function closeModal(): void {
    emit('close');
}

function resetModalState(): void {
    searchQuery.value = '';
    selectedProduct.value = null;
    productQuantity.value = 1;
}

// Event Handlers
function handleEnterKey(event: KeyboardEvent): void {
    if (event.key === 'Enter' && isProductSelected.value) {
        event.preventDefault();
        addProductToCart();
    }
}

function focusSearchInput(): void {
    nextTick(() => {
        setTimeout(() => {
            if (searchInputRef.value?.$el) {
                const input = searchInputRef.value.$el.querySelector('input');
                if (input) {
                    input.focus();
                }
            }
        }, 100);
    });
}

// Watchers
watch(searchResults, (results: Product[]) => {
    // Auto-select when only one product matches
    if (results.length === 1) {
        selectedProduct.value = results[0];
    } else if (results.length === 0) {
        selectedProduct.value = null;
    }
});

watch(() => props.open, (isOpen: boolean) => {
    if (isOpen) {
        focusSearchInput();
    } else {
        resetModalState();
    }
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 bg-black/40">
        <div class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-900 rounded-lg shadow-lg w-[600px] max-h-[80vh] overflow-hidden" @keydown="handleEnterKey">
            <div class="p-6 pb-4 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Add Product</h2>
                    <Button variant="ghost" size="icon" @click="closeModal">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="space-y-2">
                    <Label for="search">Search Product</Label>
                    <Input
                        id="search"
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by product name"
                        autocomplete="off"
                        @keydown="handleEnterKey"
                    />
                </div>

                <div v-if="hasSearchQuery && !hasSearchResults" class="flex items-center justify-center py-8 text-muted-foreground">
                    <p>No products found matching "{{ searchQuery }}"</p>
                </div>

                <div v-if="hasSearchResults" class="space-y-2">
                    <Label>Search Results ({{ searchResults.length }})</Label>
                    <div class="max-h-40 overflow-y-auto border rounded-md">
                        <div
                            v-for="product in searchResults"
                            :key="product.id"
                            @click="selectProduct(product)"
                            class="cursor-pointer p-3 hover:bg-muted border-b last:border-b-0 transition-colors"
                            :class="{ 'bg-muted': selectedProduct?.id === product.id }"
                        >
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <p class="font-medium">{{ product.product_name }}</p>
                                </div>
                                <Badge variant="outline">₱{{ product.unit_price }}</Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isProductSelected" class="space-y-4 border-t pt-4">
                    <Label>Product Details</Label>
                    <Card>
                        <CardContent class="p-4 space-y-3">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Name:</span>
                                    <p class="font-medium">{{ selectedProduct!.product_name }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Unit Price:</span>
                                    <p class="font-medium">₱{{ selectedProduct!.unit_price }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Status:</span>
                                    <Badge :variant="selectedProduct!.status === 'active' ? 'default' : 'secondary'">
                                        {{ selectedProduct!.status }}
                                    </Badge>
                                </div>
                            </div>
                            
                            <!-- Quantity Section -->
                            <div class="border-t pt-3">
                                <div class="space-y-2">
                                    <Label for="quantity">Quantity</Label>
                                    <Input
                                        id="quantity"
                                        v-model.number="productQuantity"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="w-24"
                                        @keydown="handleEnterKey"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="p-6 pt-4 border-t bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div v-if="isProductSelected" class="text-sm text-muted-foreground">
                        Selected: {{ selectedProduct!.product_name }}
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        Select a product to continue
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="closeModal">
                            Cancel
                        </Button>
                        <Button 
                            :disabled="!isProductSelected" 
                            @click="addProductToCart"
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
