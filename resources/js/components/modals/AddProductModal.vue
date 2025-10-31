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
        <DialogContent class="max-h-[85vh] max-w-2xl overflow-hidden">
            <DialogHeader class="pb-2 text-center">
                <DialogTitle
                    class="flex items-center justify-center gap-2 text-xl"
                >
                    <ShoppingCart class="h-5 w-5" />
                    Add Item to Cart
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Search and select products to add to your sale
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 space-y-5 overflow-y-auto">
                <!-- Search Section -->
                <div class="space-y-3">
                    <Label
                        for="search"
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Search Products
                    </Label>
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
                                'border-blue-200 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/20':
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
                <div v-if="isProductSelected" class="space-y-4 border-t pt-4">
                    <Label
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Selected Product
                    </Label>

                    <div class="space-y-4 rounded-lg bg-muted/30 p-4">
                        <!-- Product Info -->
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold">
                                    {{ selectedProduct!.product_name }}
                                </h3>
                                <div class="mt-1">
                                    <span class="text-sm text-muted-foreground"
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
                        <div class="grid grid-cols-2 items-end gap-4">
                            <div class="space-y-2">
                                <Label
                                    for="quantity"
                                    class="text-sm font-medium"
                                    >Quantity</Label
                                >
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
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Total Price</Label
                                >
                                <div
                                    class="flex h-10 items-center justify-center rounded-md border border-green-200 bg-green-50 dark:border-green-700 dark:bg-green-950/20"
                                >
                                    <span
                                        class="text-lg font-bold text-green-700 dark:text-green-400"
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
                        class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20"
                    >
                        <Search
                            class="h-10 w-10 text-blue-600 dark:text-blue-400"
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
            <div class="flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    <span v-if="isProductSelected">
                        Ready to add:
                        <span class="font-medium">{{
                            selectedProduct!.product_name
                        }}</span>
                    </span>
                    <span v-else> Select a product to continue </span>
                </div>

                <div class="flex gap-3">
                    <Button variant="outline" @click="isOpen = false">
                        Cancel
                    </Button>
                    <Button
                        :disabled="!isProductSelected"
                        @click="addProductToCart"
                        class="bg-blue-600 text-white hover:bg-blue-700"
                    >
                        <ShoppingCart class="mr-2 h-4 w-4" />
                        Add to Cart
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
