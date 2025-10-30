<script lang="ts" setup>
import { ref, watch, computed, nextTick } from 'vue';
import type { Product } from '@/types/pos';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Search, Package, ShoppingCart } from 'lucide-vue-next';

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
    'update:open': [value: boolean];
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
const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value)
});

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

const totalPrice = computed((): number => {
    if (!selectedProduct.value) return 0;
    return Number(selectedProduct.value.unit_price) * productQuantity.value;
});

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
    
    // Reset after adding
    resetModalState();
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
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl max-h-[85vh] overflow-hidden">
            <DialogHeader class="text-center pb-2">
                <DialogTitle class="text-xl flex items-center justify-center gap-2">
                    <ShoppingCart class="w-5 h-5" />
                    Add Item to Cart
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Search and select products to add to your sale
                </DialogDescription>
            </DialogHeader>
            
            <div class="space-y-5 overflow-y-auto flex-1">
                <!-- Search Section -->
                <div class="space-y-3">
                    <Label for="search" class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                        Search Products
                    </Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
                        <Input
                            id="search"
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Type product name to search..."
                            class="pl-10"
                            autocomplete="off"
                            @keydown="handleEnterKey"
                        />
                    </div>
                </div>

                <!-- No Results -->
                <div v-if="hasSearchQuery && !hasSearchResults" class="text-center py-8 space-y-3">
                    <div class="w-16 h-16 mx-auto bg-muted rounded-full flex items-center justify-center">
                        <Package class="w-8 h-8 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="font-medium text-muted-foreground">No products found</p>
                        <p class="text-sm text-muted-foreground">Try searching with different keywords</p>
                    </div>
                </div>

                <!-- Search Results -->
                <div v-if="hasSearchResults" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                            Products Found ({{ searchResults.length }})
                        </Label>
                    </div>
                    
                    <div class="max-h-48 overflow-y-auto border rounded-lg">
                        <div
                            v-for="product in searchResults"
                            :key="product.id"
                            @click="selectProduct(product)"
                            class="cursor-pointer p-4 hover:bg-muted/50 border-b border-border/50 last:border-0 transition-colors"
                            :class="{ 'bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-700': selectedProduct?.id === product.id }"
                        >
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm">{{ product.product_name }}</h4>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-lg">₱{{ Number(product.unit_price).toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Product Details -->
                <div v-if="isProductSelected" class="space-y-4 border-t pt-4">
                    <Label class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                        Selected Product
                    </Label>
                    
                    <div class="bg-muted/30 p-4 rounded-lg space-y-4">
                        <!-- Product Info -->
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg">{{ selectedProduct!.product_name }}</h3>
                                <div class="mt-1">
                                    <span class="text-sm text-muted-foreground">₱{{ Number(selectedProduct!.unit_price).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quantity Input -->
                        <div class="grid grid-cols-2 gap-4 items-end">
                            <div class="space-y-2">
                                <Label for="quantity" class="text-sm font-medium">Quantity</Label>
                                <Input
                                    id="quantity"
                                    v-model.number="productQuantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="text-center text-lg font-semibold"
                                    @keydown="handleEnterKey"
                                />
                            </div>
                            
                            <!-- Total Price -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium text-muted-foreground">Total Price</Label>
                                <div class="h-10 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-700 rounded-md flex items-center justify-center">
                                    <span class="text-lg font-bold text-green-700 dark:text-green-400">
                                        ₱{{ totalPrice.toFixed(2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State - Show when no search query -->
                <div v-if="!hasSearchQuery" class="text-center py-12 space-y-4">
                    <div class="w-20 h-20 mx-auto bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                        <Search class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="font-medium text-lg">Search for Products</p>
                        <p class="text-sm text-muted-foreground mt-1">Start typing to find products to add to your cart</p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between pt-4 border-t">
                <div class="text-sm text-muted-foreground">
                    <span v-if="isProductSelected">
                        Ready to add: <span class="font-medium">{{ selectedProduct!.product_name }}</span>
                    </span>
                    <span v-else>
                        Select a product to continue
                    </span>
                </div>
                
                <div class="flex gap-3">
                    <Button variant="outline" @click="isOpen = false">
                        Cancel
                    </Button>
                    <Button 
                        :disabled="!isProductSelected" 
                        @click="addProductToCart"
                        class="bg-blue-600 hover:bg-blue-700 text-white"
                    >
                        <ShoppingCart class="w-4 h-4 mr-2" />
                        Add to Cart
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>