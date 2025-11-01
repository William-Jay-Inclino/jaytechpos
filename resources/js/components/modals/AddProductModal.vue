<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Product } from '@/types/pos';
import { Package, Search, ShoppingCart } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

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
    set: (value) => emit('update:open', value),
});

const searchResults = computed((): Product[] => {
    const query = searchQuery.value.trim();
    if (!query) return [];

    const searchTerm = query.toLowerCase();
    return props.products.filter((product: Product) => {
        return product.product_name.toLowerCase().includes(searchTerm);
    });
});

const hasSearchResults = computed(
    (): boolean => searchResults.value.length > 0,
);
const hasSearchQuery = computed(
    (): boolean => searchQuery.value.trim().length > 0,
);
const isProductSelected = computed(
    (): boolean => selectedProduct.value !== null,
);

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
        quantity: productQuantity.value,
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

watch(
    () => props.open,
    (isOpen: boolean) => {
        if (isOpen) {
            focusSearchInput();
        } else {
            resetModalState();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[85vh] max-w-2xl flex flex-col">
            <DialogHeader class="pb-2 text-center flex-shrink-0">
                <DialogTitle
                    class="flex items-center justify-center gap-2 text-xl"
                >
                    <ShoppingCart class="h-5 w-5" />
                    Add Item
                </DialogTitle>
            </DialogHeader>

            <div class="flex-1 space-y-5 overflow-y-auto min-h-0">
                <!-- Search Section -->
                <div class="space-y-3">
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground"
                        />
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
                <div
                    v-if="hasSearchQuery && !hasSearchResults"
                    class="space-y-3 py-8 text-center"
                >
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-muted"
                    >
                        <Package class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="font-medium text-muted-foreground">
                            No products found
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Try searching with different keywords
                        </p>
                    </div>
                </div>

                <!-- Search Results -->
                <div v-if="hasSearchResults" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label
                            class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Products Found ({{ searchResults.length }})
                        </Label>
                    </div>

                    <div class="max-h-48 overflow-y-auto rounded-lg border">
                        <div
                            v-for="product in searchResults"
                            :key="product.id"
                            @click="selectProduct(product)"
                            class="cursor-pointer border-b border-border/50 p-4 transition-colors last:border-0 hover:bg-muted/50"
                            :class="{
                                'border-orange-200 bg-orange-50 dark:border-orange-700 dark:bg-orange-950/20':
                                    selectedProduct?.id === product.id,
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium">
                                        {{ product.product_name }}
                                    </h4>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-semibold">
                                        ₱{{
                                            Number(product.unit_price).toFixed(
                                                2,
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Product Details -->
                <div v-if="isProductSelected" class="space-y-4 border-t-2 border-orange-200 bg-gradient-to-r from-orange-50/50 to-green-50/50 p-4 rounded-lg dark:border-orange-600 dark:from-orange-950/30 dark:to-green-950/30">
                    <Label
                        class="text-sm font-bold tracking-wide text-orange-700 uppercase flex items-center gap-2 dark:text-orange-400"
                    >
                        <div class="h-2 w-2 rounded-full bg-orange-600 dark:bg-orange-400"></div>
                        Selected Product
                    </Label>

                    <div class="space-y-4 rounded-lg bg-white/80 border-2 border-orange-100 p-5 shadow-sm dark:bg-gray-900/80 dark:border-orange-800">
                        <!-- Product Info -->
                        <div class="flex items-start justify-between border-b border-orange-100 pb-4 dark:border-orange-700">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ selectedProduct!.product_name }}
                                </h3>
                                <div class="mt-2">
                                    <span class="text-base font-semibold text-orange-600 dark:text-orange-400"
                                        >₱{{
                                            Number(
                                                selectedProduct!.unit_price,
                                            ).toFixed(2)
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Quantity Input -->
                        <div class="grid grid-cols-2 items-end gap-6 pt-4">
                            <div class="space-y-3">
                                <Label
                                    for="quantity"
                                    class="text-sm font-bold text-gray-700 dark:text-gray-300"
                                    >Qty</Label
                                >
                                <div
                                    class="flex h-12 items-center justify-center rounded-lg border-3 border-orange-300 bg-gradient-to-r from-orange-100 to-orange-200 shadow-md dark:border-orange-600 dark:from-orange-900/40 dark:to-orange-800/40"
                                >
                                    <Input
                                        id="quantity"
                                        v-model.number="productQuantity"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="text-center text-xl font-black bg-transparent border-0 focus:ring-0 focus:outline-0 text-orange-800 dark:text-orange-300 w-full h-full"
                                        @keydown="handleEnterKey"
                                    />
                                </div>
                            </div>

                            <!-- Total Price -->
                            <div class="space-y-3">
                                <Label
                                    class="text-sm font-bold text-gray-700 dark:text-gray-300"
                                    >Amount</Label
                                >
                                <div
                                    class="flex h-12 items-center justify-center rounded-lg border-3 border-green-300 bg-gradient-to-r from-green-100 to-green-200 shadow-md dark:border-green-600 dark:from-green-900/40 dark:to-green-800/40"
                                >
                                    <span
                                        class="text-xl font-black text-green-800 dark:text-green-300"
                                    >
                                        ₱{{ totalPrice.toFixed(2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State - Show when no search query -->
                <div v-if="!hasSearchQuery" class="space-y-4 py-12 text-center">
                    <div
                        class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/20"
                    >
                        <Search
                            class="h-10 w-10 text-orange-600 dark:text-orange-400"
                        />
                    </div>
                    <div>
                        <p class="text-lg font-medium">Search for Products</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Start typing to find products to add to your cart
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="space-y-3 border-t pt-4 flex-shrink-0">
                <Button
                    :disabled="!isProductSelected"
                    @click="addProductToCart"
                    class="w-full"
                    size="lg"
                >
                    <ShoppingCart class="mr-2 h-4 w-4" />
                    Add to Cart
                </Button>
                <Button variant="outline" @click="isOpen = false" class="w-full">
                    Cancel
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
