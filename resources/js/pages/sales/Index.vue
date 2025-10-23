<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

// Layout & Components
import AppLayout from '@/layouts/AppLayout.vue';
import AddProductModal from '@/components/AddProductModal.vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Types
import type { BreadcrumbItem } from '@/types';
import { Product } from '@/types/pos';
import { Customer, CartItem } from '@/types/pos';

const props = defineProps<{ 
    products: Product[];
    customers: Customer[];
    sale?: SaleData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    // { title: 'Sales', href: '/sales' },
    { title: 'New Sale', href: '/sales/create' },
];

// Types for sale response
interface SaleResponse {
    id: number;
    invoice_number: string;
    transaction_date: string;
    customer_id: number | null;
    customer_name: string | null;
    total_amount: number;
    paid_amount: number;
    payment_type: string;
    is_utang: boolean;
    notes: string | null;
    cashier: string;
    items: Array<{
        id: number;
        product_id: number;
        product_name: string;
        quantity: number;
        unit_price: number;
        total_amount: number;
    }>;
    created_at?: string;
    updated_at?: string;
}

// Wrapper for Laravel API Resource
interface SaleData {
    data: SaleResponse;
}

// Reactive State
const isAddModalOpen = ref(false);
const cartItems = ref<CartItem[]>([]);
const selectedCustomerId = ref<string>('0');
const paymentMethod = ref<string>('cash');
const paidAmount = ref(0);
const amountTendered = ref(0);
const showSuccessModal = ref(false);
const saleData = ref<SaleResponse | null>(null);
const isProcessing = ref(false);

// Business Logic Functions
function addProductToCart(data: { product: Product; quantity: number }): void {
    const { product, quantity } = data;
    
    const existingItem = cartItems.value.find((item: CartItem) => item.id === product.id);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cartItems.value.push({
            ...product,
            quantity,
        });
    }
    
    isAddModalOpen.value = false;
}

function removeCartItem(index: number): void {
    cartItems.value.splice(index, 1);
}

function updateCartItemQuantity(index: number, quantity: number): void {
    if (quantity > 0) {
        cartItems.value[index].quantity = quantity;
    }
}

function calculateItemTotal(item: CartItem): number {
    return (Number(item.unit_price) * item.quantity);
}

// Computed Properties
const cartTotalAmount = computed((): number => {
    return cartItems.value.reduce((sum: number, item: CartItem) => sum + calculateItemTotal(item), 0);
});

const totalItemsInCart = computed((): number => {
    return cartItems.value.reduce((sum: number, item: CartItem) => sum + item.quantity, 0);
});

const changeAmount = computed((): number => {
    return Math.max(0, amountTendered.value - cartTotalAmount.value);
});

const isCheckoutValid = computed((): boolean => {
    const hasItems = cartItems.value.length > 0;
    
    if (paymentMethod.value === 'cash') {
        return hasItems && amountTendered.value >= cartTotalAmount.value;
    } else if (paymentMethod.value === 'utang') {
        return hasItems && selectedCustomerId.value !== '0' && paidAmount.value >= 0;
    }
    
    return false;
});

// Event Handlers
function handleCheckout(): void {
    if (!isCheckoutValid.value || isProcessing.value) return;
    
    isProcessing.value = true;
    
    const checkoutData = {
        customer_id: selectedCustomerId.value !== '0' ? parseInt(selectedCustomerId.value) : null,
        items: cartItems.value.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: Number(item.unit_price),
        })),
        total_amount: cartTotalAmount.value,
        paid_amount: paymentMethod.value === 'cash' ? cartTotalAmount.value : paidAmount.value,
        payment_type: paymentMethod.value,
        notes: null,
    };
    
    console.log('Processing checkout...', checkoutData);
    
    // Use Inertia router instead of fetch
    router.post('/sales', checkoutData, {
        onSuccess: () => {
            console.log('✅ Sale completed successfully!');
            // Sale data will be available in props after successful submission
        },
        onError: (errors) => {
            console.error('❌ Checkout failed:', errors);
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        }
    });
}

function resetForm(): void {
    cartItems.value = [];
    selectedCustomerId.value = '0';
    paymentMethod.value = 'cash';
    paidAmount.value = 0;
    amountTendered.value = 0;
    showSuccessModal.value = false;
    saleData.value = null;
}

function printReceipt(): void {
    window.print();
}

function closeSuccessModal(): void {
    showSuccessModal.value = false;
    resetForm();
}

function handleKeyboardShortcuts(event: KeyboardEvent): void {
    if (event.ctrlKey && event.key === 'a') {
        event.preventDefault();
        isAddModalOpen.value = true;
    } else if (event.ctrlKey && event.key === 'Enter' && isCheckoutValid.value) {
        event.preventDefault();
        handleCheckout();
    }
}

// Add keyboard event listener when component mounts
onMounted(() => {
    document.addEventListener('keydown', handleKeyboardShortcuts);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboardShortcuts);
});

// Watch for sale prop changes to show success modal
watch(() => props.sale, (newSale: SaleData | undefined) => {
    if (newSale && newSale.data) {
        console.log('✅ Sale completed successfully!', newSale.data.invoice_number);
        saleData.value = newSale.data;
        showSuccessModal.value = true;
        isProcessing.value = false;
    }
}, { immediate: true });

</script>

<template>
    <Head title="Create Sale" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-2 py-6 md:px-8 md:py-10">
            <!-- Page Header -->
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center gap-4 mb-6">
                    <h1 class="text-xl md:text-2xl font-semibold tracking-tight">🛒 New Sale</h1>
                </div>

                <!-- Add Item Button -->
                <div class="flex flex-col items-end gap-1 mb-6">
                    <Button size="lg" @click="isAddModalOpen = true">
                        Add Item
                        <span class="ml-2 text-xs opacity-90">(Ctrl+A)</span>
                    </Button>
                </div>

                <!-- Customer Selection -->
                <div class="mb-6">
                    <Label for="customer" class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 block">
                        Customer 
                        <span v-if="paymentMethod === 'utang'" class="text-red-500">*</span>
                        <span v-else class="text-gray-500">(Optional)</span>
                    </Label>
                    <Select v-model="selectedCustomerId">
                        <SelectTrigger class="w-full max-w-md">
                            <SelectValue placeholder="Select a customer or leave empty for walk-in" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">Walk-in Customer</SelectItem>
                            <SelectItem 
                                v-for="customer in customers" 
                                :key="customer.id" 
                                :value="customer.id.toString()"
                            >
                                {{ customer.name }}
                                <span v-if="customer.mobile_number" class="text-gray-500 ml-2">
                                    ({{ customer.mobile_number }})
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Payment Method Selection -->
                <div class="mb-6">
                    <Label for="paymentMethod" class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 block">Payment Method</Label>
                    <Select v-model="paymentMethod">
                        <SelectTrigger class="w-full max-w-md">
                            <SelectValue placeholder="Select payment method" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="cash">💵 Cash Payment</SelectItem>
                            <SelectItem value="utang">📝 Credit (Utang)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                        <AddProductModal
                            :open="isAddModalOpen"
                            :products="products"
                            @close="isAddModalOpen = false"
                            @add="addProductToCart"
                        />

                        <!-- Items Table -->
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <Table class="min-w-full text-sm">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="whitespace-nowrap">Product</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Qty</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Unit Price</TableHead>
                                        <TableHead class="text-right whitespace-nowrap">Total</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <!-- Dynamic product rows -->
                                    <TableRow v-for="(item, index) in cartItems" :key="item.id">
                                        <TableCell class="flex items-center gap-3 py-3">
                                            <Avatar class="h-16 w-16 rounded-md">
                                                <AvatarFallback class="text-lg font-semibold">{{ item.product_name.charAt(0).toUpperCase() }}</AvatarFallback>
                                            </Avatar>
                                            <div class="min-w-0 flex-1">
                                                <span class="truncate block font-medium text-sm">{{ item.product_name }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right align-middle">
                                            <input 
                                                type="number" 
                                                min="1" 
                                                :value="item.quantity"
                                                @input="updateCartItemQuantity(index, parseInt(($event.target as HTMLInputElement).value))"
                                                class="w-16 px-2 py-1 text-right border rounded text-sm"
                                            />
                                        </TableCell>
                                        <TableCell class="text-right align-middle">₱{{ Number(item.unit_price).toFixed(2) }}</TableCell>
                                        <TableCell class="text-right align-middle font-medium">
                                            ₱{{ calculateItemTotal(item).toFixed(2) }}
                                        </TableCell>
                                        <TableCell class="align-middle">
                                            <Button variant="ghost" size="icon" @click="removeCartItem(index)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <!-- Empty state -->
                                    <TableRow v-if="!cartItems.length">
                                        <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                                            No items added. Click "+ Add Item" to start building your sale.
                                        </TableCell>
                                    </TableRow>

                                    <!-- Totals Row -->
                                    <TableRow v-if="cartItems.length" class="font-bold bg-gray-50 dark:bg-gray-800 text-base border-t-2 border-gray-200 dark:border-gray-700">
                                        <TableCell class="text-gray-900 dark:text-gray-100">Totals</TableCell>
                                        <TableCell class="text-right text-gray-900 dark:text-gray-100">{{ totalItemsInCart }}</TableCell>
                                        <TableCell class="text-right"></TableCell>
                                        <TableCell class="text-right text-gray-900 dark:text-gray-100">₱{{ cartTotalAmount.toFixed(2) }}</TableCell>
                                        <TableCell></TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Payment Summary -->
                        <div v-if="cartItems.length" class="mt-6 space-y-4">
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Payment Summary</h3>
                                
                                <!-- Cash Payment Layout -->
                                <div v-if="paymentMethod === 'cash'" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <!-- Total Amount Display -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium text-gray-900 dark:text-gray-100">Total Amount</Label>
                                        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                                            <span class="text-2xl font-bold text-orange-700 dark:text-orange-400">₱{{ cartTotalAmount.toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Amount Tendered Input -->
                                    <div class="space-y-2">
                                        <Label for="amountTendered" class="text-sm font-medium text-gray-900 dark:text-gray-100">Amount Tendered</Label>
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
                                        <Label class="text-sm font-medium text-gray-900 dark:text-gray-100">Change</Label>
                                        <div class="p-3 rounded-lg border h-12 flex items-center justify-end" :class="changeAmount > 0 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'">
                                            <span class="text-lg font-bold" :class="changeAmount > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">₱{{ changeAmount.toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Utang Payment Layout -->
                                <div v-if="paymentMethod === 'utang'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <!-- Total Amount Display -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium text-gray-900 dark:text-gray-100">Total Amount</Label>
                                        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                                            <span class="text-2xl font-bold text-orange-700 dark:text-orange-400">₱{{ cartTotalAmount.toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Paid Amount Input -->
                                    <div class="space-y-2">
                                        <Label for="paidAmount" class="text-sm font-medium text-gray-900 dark:text-gray-100">Amount Paid <span class="text-red-500">*</span></Label>
                                        <Input
                                            id="paidAmount"
                                            v-model.number="paidAmount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="text-right text-lg font-semibold h-12"
                                        />
                                    </div>

                                    <!-- Balance Display -->
                                    <div v-if="paidAmount < cartTotalAmount" class="md:col-span-2 space-y-2">
                                        <Label class="text-sm font-medium text-gray-900 dark:text-gray-100">Remaining Balance</Label>
                                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                            <span class="text-xl font-bold text-red-700 dark:text-red-400">₱{{ (cartTotalAmount - paidAmount).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Checkout Button -->
                                <div class="flex justify-center">
                                    <Button 
                                        variant="destructive" 
                                        size="lg" 
                                        class="w-full md:w-auto min-w-[200px] h-12 text-lg font-semibold"
                                        :disabled="!isCheckoutValid || isProcessing"
                                        @click="handleCheckout"
                                    >
                                        <span v-if="isProcessing">Processing...</span>
                                        <span v-else>Complete Checkout</span>
                                        <span v-if="!isProcessing" class="ml-2 text-sm opacity-80">(Ctrl+Enter)</span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>

        <!-- Success Modal with Receipt -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-green-600 dark:text-green-400">✅ Sale Completed!</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Transaction processed successfully</p>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-content" class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="text-center mb-4">
                        <h3 class="font-bold text-lg">Free POS</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Point of Sale Receipt</p>
                        <div class="border-b border-gray-300 dark:border-gray-600 my-2"></div>
                    </div>

                    <!-- Receipt Details -->
                    <div v-if="saleData" class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Invoice #:</span>
                            <span class="font-mono">{{ saleData.invoice_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Date:</span>
                            <span>{{ new Date(saleData.transaction_date).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Customer:</span>
                            <span>{{ saleData.customer_name || 'Walk-in Customer' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Cashier:</span>
                            <span>{{ saleData.cashier }}</span>
                        </div>
                        <div class="border-b border-gray-300 dark:border-gray-600 my-3"></div>

                        <!-- Items -->
                        <div class="space-y-2">
                            <div v-for="item in saleData.items" :key="item.id" class="text-xs">
                                <div class="flex justify-between">
                                    <span class="font-medium">{{ item.product_name }}</span>
                                    <span class="font-medium">₱{{ item.total_amount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>{{ item.quantity }} × ₱{{ item.unit_price.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-b border-gray-300 dark:border-gray-600 my-3"></div>

                        <!-- Totals -->
                        <div class="space-y-1">
                            <div class="flex justify-between font-bold text-lg border-t border-gray-300 dark:border-gray-600 pt-1">
                                <span>TOTAL:</span>
                                <span>₱{{ saleData.total_amount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ saleData.payment_type === 'cash' ? 'Amount Tendered:' : 'Amount Paid:' }}</span>
                                <span>₱{{ saleData.paid_amount.toFixed(2) }}</span>
                            </div>
                            <div v-if="saleData.payment_type === 'cash'" class="flex justify-between font-semibold text-green-600 dark:text-green-400">
                                <span>Change:</span>
                                <span>₱{{ (saleData.paid_amount - saleData.total_amount).toFixed(2) }}</span>
                            </div>
                            <div v-if="saleData.payment_type === 'utang' && saleData.paid_amount < saleData.total_amount" class="flex justify-between font-semibold text-red-600 dark:text-red-400">
                                <span>Balance Due:</span>
                                <span>₱{{ (saleData.total_amount - saleData.paid_amount).toFixed(2) }}</span>
                            </div>
                        </div>

                        <div class="border-b border-gray-300 dark:border-gray-600 my-3"></div>
                        <div class="text-center text-xs text-gray-500">
                            <p>Thank you for your business!</p>
                            <p>{{ new Date().toLocaleString() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                    <Button variant="outline" class="flex-1" @click="printReceipt">
                        🖨️ Print Receipt
                    </Button>
                    <Button variant="default" class="flex-1" @click="closeSuccessModal">
                        Close & New Sale
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt-content,
    #receipt-content * {
        visibility: visible;
    }
    #receipt-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
