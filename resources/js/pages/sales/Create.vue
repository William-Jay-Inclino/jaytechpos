<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem, type Product } from '@/types';
    import AddProductModal from '@/components/AddProductModal.vue';
    import { Head, Link } from '@inertiajs/vue3';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
    } from '@/components/ui/select';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
        CardDescription,
    } from '@/components/ui/card';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from '@/components/ui/table';
    import { Trash2, Plus } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sales', href: '/sales' },
    { title: 'New Sale', href: '/sales/create' },
];

const props = defineProps<{ products: Product[] }>();
console.log('Products:', props.products);

import { ref, computed, onMounted, onUnmounted } from 'vue';
const showAddModal = ref(false); // Ensure modal starts closed
const saleItems = ref<Product[]>([]);

// Sale item interface with quantity
interface SaleItem extends Product {
    quantity: number;
    discount: number;
}

const saleItemsWithQty = ref<SaleItem[]>([]);

function handleAddProduct(data: { product: Product; quantity: number }) {
    const { product, quantity } = data;
    
    // Check if product already exists in cart
    const existingItem = saleItemsWithQty.value.find(item => item.id === product.id);
    
    if (existingItem) {
        // Increase quantity by the specified amount
        existingItem.quantity += quantity;
    } else {
        // Add new product with specified quantity
        saleItemsWithQty.value.push({
            ...product,
            quantity: quantity,
            discount: 0
        });
    }
    
    showAddModal.value = false;
}

function removeItem(index: number) {
    saleItemsWithQty.value.splice(index, 1);
}

function updateQuantity(index: number, quantity: number) {
    if (quantity > 0) {
        saleItemsWithQty.value[index].quantity = quantity;
    }
}

// Computed totals
const subtotal = computed(() => {
    return saleItemsWithQty.value.reduce((sum, item) => {
        const itemPrice = Number(item.unit_price) || 0;
        return sum + (itemPrice * item.quantity) - item.discount;
    }, 0);
});

const totalVat = computed(() => {
    return saleItemsWithQty.value.reduce((sum, item) => {
        const itemPrice = Number(item.unit_price) || 0;
        const itemSubtotal = (itemPrice * item.quantity) - item.discount;
        const vatRate = item.vat?.rate_percentage || 0;
        return sum + (itemSubtotal * vatRate / 100);
    }, 0);
});

const totalDiscount = computed(() => {
    return saleItemsWithQty.value.reduce((sum, item) => sum + item.discount, 0);
});

const grandTotal = computed(() => {
    return subtotal.value + totalVat.value;
});

const netAmount = computed(() => {
    return grandTotal.value - totalDiscount.value;
});

// Payment fields
const amountTendered = ref(0);
const change = computed(() => {
    return Math.max(0, amountTendered.value - netAmount.value);
});

function handleCheckout() {
    // TODO: Implement checkout logic
    console.log('Processing checkout...', {
        items: saleItemsWithQty.value,
        netAmount: netAmount.value,
        amountTendered: amountTendered.value,
        change: change.value
    });
}

// Keyboard shortcut handler
function handleKeydown(event: KeyboardEvent) {
    // Ctrl+A to open Add Item modal
    if (event.ctrlKey && event.key === 'a') {
        event.preventDefault();
        showAddModal.value = true;
    }
    // Ctrl+Enter to checkout
    else if (event.ctrlKey && event.key === 'Enter' && saleItemsWithQty.value.length > 0) {
        event.preventDefault();
        handleCheckout();
    }
}

// Add keyboard event listener when component mounts
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

</script>

<template>
    <Head title="Create Sale" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full flex justify-center px-2 py-6 md:px-8 md:py-10">
            <!-- Page Header -->
            <div class="max-w-7xl w-full">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                    <h1 class="text-xl md:text-2xl font-semibold tracking-tight">🛒 New Sale</h1>
                    <div class="flex gap-2">
                        <Link href="/sales">
                            <Button variant="outline" size="lg" class="min-w-[96px]">Cancel</Button>
                        </Link>
                    </div>
                </div>

                <!-- Particulars Card -->
                <Card class="border rounded-xl w-full max-w-7xl mx-auto shadow-none">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-medium">Particulars</CardTitle>
                        <CardDescription class="text-xs text-gray-500">Add items to this sale</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Customer Info -->
                        <div class="space-y-2 mb-2">
                            <Label for="customer" class="text-sm font-medium">Customer</Label>
                            <div class="flex gap-2 flex-col sm:flex-row">
                                <Select class="flex-1 min-w-0">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select customer" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Juan Dela Cruz</SelectItem>
                                        <SelectItem value="2">Maria Santos</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline" size="icon" title="Add new customer" class="shrink-0">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Add Item Button -->
                        <div class="flex flex-col items-end gap-1 mb-2">
                            <Button variant="secondary" size="lg" class="font-semibold px-4 py-2" @click="showAddModal = true">
                                Add Item
                                <span class="ml-2 text-xs opacity-70">(Ctrl+A)</span>
                            </Button>
                        </div>

                        <AddProductModal
                            :open="showAddModal"
                            :products="products"
                            @close="showAddModal = false"
                            @add="handleAddProduct"
                        />

                        <!-- Items Table -->
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <Table class="min-w-full text-sm">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="whitespace-nowrap">Product</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Qty</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Unit Price</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Discount</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">VAT</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Total</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <!-- Dynamic product rows -->
                                    <TableRow v-for="(item, index) in saleItemsWithQty" :key="item.id">
                                        <TableCell class="flex items-center gap-3 py-3">
                                            <Avatar class="h-16 w-16 rounded-md">
                                                <AvatarFallback class="text-lg font-semibold">{{ item.product_name.charAt(0).toUpperCase() }}</AvatarFallback>
                                            </Avatar>
                                            <div class="min-w-0 flex-1">
                                                <span class="truncate block font-medium text-sm">{{ item.product_name }}</span>
                                                <span class="text-xs text-muted-foreground block">SKU: {{ item.sku }}</span>
                                                <span v-if="item.barcode" class="text-xs text-muted-foreground block">Barcode: {{ item.barcode }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right align-middle">
                                            <input 
                                                type="number" 
                                                min="1" 
                                                :value="item.quantity"
                                                @input="updateQuantity(index, parseInt(($event.target as HTMLInputElement).value))"
                                                class="w-16 px-2 py-1 text-right border rounded text-sm"
                                            />
                                        </TableCell>
                                        <TableCell class="text-right align-middle">₱{{ Number(item.unit_price).toFixed(2) }}</TableCell>
                                        <TableCell class="text-right align-middle">₱{{ item.discount.toFixed(2) }}</TableCell>
                                        <TableCell class="text-right align-middle">
                                            ₱{{ ((Number(item.unit_price) * item.quantity - item.discount) * (item.vat?.rate_percentage || 0) / 100).toFixed(2) }}
                                        </TableCell>
                                        <TableCell class="text-right align-middle font-medium">
                                            ₱{{ ((Number(item.unit_price) * item.quantity - item.discount) + ((Number(item.unit_price) * item.quantity - item.discount) * (item.vat?.rate_percentage || 0) / 100)).toFixed(2) }}
                                        </TableCell>
                                        <TableCell class="align-middle">
                                            <Button variant="ghost" size="icon" @click="removeItem(index)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <!-- Empty state -->
                                    <TableRow v-if="!saleItemsWithQty.length">
                                        <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                                            No items added. Click "+ Add Item" to start building your sale.
                                        </TableCell>
                                    </TableRow>

                                    <!-- Totals Row -->
                                    <TableRow v-if="saleItemsWithQty.length" class="font-bold bg-gray-50 text-base border-t-2">
                                        <TableCell>Totals</TableCell>
                                        <TableCell class="text-right">{{ saleItemsWithQty.reduce((sum, item) => sum + item.quantity, 0) }}</TableCell>
                                        <TableCell class="text-right"></TableCell>
                                        <TableCell class="text-right">₱{{ totalDiscount.toFixed(2) }}</TableCell>
                                        <TableCell class="text-right">₱{{ totalVat.toFixed(2) }}</TableCell>
                                        <TableCell class="text-right">₱{{ grandTotal.toFixed(2) }}</TableCell>
                                        <TableCell></TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Payment Summary -->
                        <div v-if="saleItemsWithQty.length" class="mt-6 space-y-4">
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-semibold mb-4">Payment Summary</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <!-- Net Amount Display -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">Net Amount</Label>
                                        <div class="p-3 bg-orange-50 rounded-lg border">
                                            <span class="text-2xl font-bold text-orange-700">₱{{ netAmount.toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Amount Tendered Input -->
                                    <div class="space-y-2">
                                        <Label for="amountTendered" class="text-sm font-medium">Amount Tendered</Label>
                                        <Input
                                            id="amountTendered"
                                            v-model.number="amountTendered"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="text-right text-lg font-semibold h-12"
                                        />
                                    </div>

                                    <!-- Change Display -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">Change</Label>
                                        <div class="p-3 rounded-lg border h-12 flex items-center justify-end" :class="change > 0 ? 'bg-green-50 border-green-200' : 'bg-gray-50'">
                                            <span class="text-lg font-bold" :class="change > 0 ? 'text-green-700' : 'text-gray-500'">₱{{ change.toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Checkout Button -->
                                <div class="flex justify-center">
                                    <Button 
                                        variant="default" 
                                        size="lg" 
                                        class="w-full md:w-auto min-w-[200px] h-12 text-lg font-semibold"
                                        :disabled="!saleItemsWithQty.length || amountTendered < netAmount"
                                        @click="handleCheckout"
                                    >
                                        Complete Checkout
                                        <span class="ml-2 text-sm opacity-80">(Ctrl+Enter)</span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
