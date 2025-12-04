<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { X, Search, UserPlus, Plus, Minus} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';

// Layout & Components
import SaleReceiptModal from '@/components/modals/SaleReceiptModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, InputCurrency } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// Utils
import { showErrorToast, showSuccessToast } from '@/lib/toast';

// Types
import type { BreadcrumbItem } from '@/types';
import { CartItem, Customer, Product } from '@/types/pos';
import { formatCurrency } from '@/utils/currency';

const props = defineProps<{
    products: Product[];
    customers: Customer[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'New Sale', href: '/sales' },
];

interface SaleResponse {
    id: number;
    invoice_number: string;
    transaction_date: string;
    customer_id: number | null;
    customer_name: string | null;
    total_amount: number;
    paid_amount: number;
    amount_tendered?: number;
    change_amount?: number;
    balance_payment?: number;
    original_customer_balance?: number;
    new_customer_balance?: number;
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

// Reactive State
const cartItems = ref<CartItem[]>([]);
const selectedCustomerId = ref<string>('0');
const paymentMethod = ref<string>('cash');
const paidAmount = ref(0);
const amountTendered = ref(0);
const payTowardsBalance = ref(false);
const deductFromBalance = ref(0);
const numericDeductFromBalance = computed((): number => {
    const v = deductFromBalance.value as unknown as string | number
    const n = typeof v === 'number' ? v : parseFloat(String(v || '0'))
    return Number.isFinite(n) ? n : 0
})
const showSuccessModal = ref(false);
const saleData = ref<SaleResponse | null>(null);
const isProcessing = ref(false);
const customerBalance = ref<number>(0);
const isLoadingBalance = ref(false);
const customerSearch = ref('');
const showCustomerDropdown = ref(false);
const productSearch = ref('');
const showProductDropdown = ref(false);
const transactionDate = ref(new Date().toISOString().split('T')[0]);
const transactionTime = ref(
    new Date().toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: false 
    })
);


// Business Logic Functions
function addProductToCart(product: Product, quantity: number = 1): void {
    const existingItem = cartItems.value.find(
        (item: CartItem) => item.id === product.id,
    );

    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cartItems.value.push({
            ...product,
            quantity,
        });
    }
}

function removeCartItem(index: number): void {
    cartItems.value.splice(index, 1);
}

function updateCartItemQuantity(index: number, quantity: number): void {
    if (quantity > 0) {
        cartItems.value[index].quantity = quantity;
    }
}

function incrementQuantity(index: number): void {
    cartItems.value[index].quantity += 1;
}

function decrementQuantity(index: number): void {
    if (cartItems.value[index].quantity > 0.01) {
        cartItems.value[index].quantity = Math.max(0.01, cartItems.value[index].quantity - 1);
    }
}

function calculateItemTotal(item: CartItem): number {
    return Number(item.unit_price) * item.quantity;
}

const cartTotalAmount = computed((): number => {
    return cartItems.value.reduce(
        (sum: number, item: CartItem) => sum + calculateItemTotal(item),
        0,
    );
});

const changeAmount = computed((): number => {
    if (payTowardsBalance.value) {
        return Math.max(
            0,
            amountTendered.value -
                cartTotalAmount.value -
                numericDeductFromBalance.value,
        );
    }
    return Math.max(0, amountTendered.value - cartTotalAmount.value);
});

const selectedCustomer = computed((): (Customer & { running_utang_balance: number }) | null => {
    if (selectedCustomerId.value === '0') return null;
    const customer = props.customers.find(
        (c) => c.id.toString() === selectedCustomerId.value,
    );
    if (!customer) return null;
    return {
        ...customer,
        running_utang_balance: customerBalance.value,
    };
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return props.customers;
    return props.customers.filter(customer =>
        customer.name.toLowerCase().includes(customerSearch.value.toLowerCase())
    );
});

const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products;
    return props.products.filter(product =>
        product.product_name.toLowerCase().includes(productSearch.value.toLowerCase())
    );
});

const selectedCustomerName = computed(() => {
    if (selectedCustomerId.value === '0') return '---';
    return selectedCustomer.value?.name || '';
});

const isCheckoutValid = computed((): boolean => {
    const hasItems = cartItems.value.length > 0;

    if (paymentMethod.value === 'cash') {
        const hasValidCustomer =
            !payTowardsBalance.value || selectedCustomerId.value !== '0';
        const hasEnoughMoney = amountTendered.value >= cartTotalAmount.value;

        if (payTowardsBalance.value) {
            const maxDeductible = Math.min(
                customerBalance.value,
                amountTendered.value - cartTotalAmount.value,
            );
            return (
                hasItems &&
                hasValidCustomer &&
                hasEnoughMoney &&
                numericDeductFromBalance.value <= maxDeductible
            );
        }

        return hasItems && hasEnoughMoney;
    } else if (paymentMethod.value === 'utang') {
        return (
            hasItems &&
            selectedCustomerId.value !== '0' &&
            paidAmount.value >= 0 &&
            paidAmount.value < cartTotalAmount.value
        );
    }

    return false;
});

const checkoutDisabledReason = computed<string | null>(() => {
    const hasItems = cartItems.value.length > 0;
    if (!hasItems) return 'Add items to cart.';

    if (paymentMethod.value === 'cash') {
        const hasValidCustomer = !payTowardsBalance.value || selectedCustomerId.value !== '0';
        const hasEnoughMoney = amountTendered.value >= cartTotalAmount.value;

        if (!hasEnoughMoney) return 'Amount tendered is less than total.';
        if (payTowardsBalance.value && !hasValidCustomer) return 'Select a customer to use change for balance.';

        const maxDeductible = Math.min(customerBalance.value, Math.max(0, amountTendered.value - cartTotalAmount.value));
        if (payTowardsBalance.value && deductFromBalance.value > maxDeductible) return 'Deduction exceeds available change or customer balance.';

        return null;
    }

    if (paymentMethod.value === 'utang') {
        if (selectedCustomerId.value === '0') return 'Select a customer for Utang.';
        if (paidAmount.value < 0) return 'Paid amount cannot be negative.';
        if (paidAmount.value >= cartTotalAmount.value) return 'For full payment, switch to Cash.';
        return null;
    }

    return null;
});

async function fetchCustomerBalance(customerId: number): Promise<void> {
    if (!customerId) {
        customerBalance.value = 0;
        return;
    }

    isLoadingBalance.value = true;
    try {
        const response = await axios.get(`/api/customers/${customerId}/balance`);
        customerBalance.value = response.data.balance;
    } catch (error) {
        customerBalance.value = 0;
    } finally {
        isLoadingBalance.value = false;
    }
}

function selectCustomer(customerId: string) {
    selectedCustomerId.value = customerId;
    showCustomerDropdown.value = false;
    customerSearch.value = '';
    
    // Fetch up-to-date balance when customer is selected
    if (customerId !== '0') {
        fetchCustomerBalance(parseInt(customerId));
    } else {
        customerBalance.value = 0;
    }
}

function selectProduct(product: Product) {
    addProductToCart(product, 1);
    showProductDropdown.value = false;
    productSearch.value = '';
}

async function handleCheckout(): Promise<void> {
    if (!isCheckoutValid.value || isProcessing.value) return;

    isProcessing.value = true;

    const checkoutData = {
        customer_id:
            selectedCustomerId.value !== '0'
                ? parseInt(selectedCustomerId.value)
                : null,
        items: cartItems.value.map((item) => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: Number(item.unit_price),
        })),
        total_amount: cartTotalAmount.value,
        paid_amount:
            paymentMethod.value === 'cash'
                ? cartTotalAmount.value
                : paidAmount.value,
        amount_tendered:
            paymentMethod.value === 'cash' ? amountTendered.value : null,
        payment_type: paymentMethod.value,
        notes: null,
        transaction_date: `${transactionDate.value} ${transactionTime.value}`,
        deduct_from_balance: payTowardsBalance.value
            ? numericDeductFromBalance.value
            : 0,
    };

    try {
        const response = await axios.post('/sales', checkoutData);
        saleData.value = response.data.sale;
        showSuccessModal.value = true;
        showSuccessToast('Sale completed successfully!');
        resetFormData();
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Failed to process the sale. Please try again.';
        showErrorToast(errorMessage);
    } finally {
        isProcessing.value = false;
    }
}

function resetFormData(): void {
    cartItems.value = [];
    selectedCustomerId.value = '0';
    paymentMethod.value = 'cash';
    paidAmount.value = 0;
    amountTendered.value = 0;
    payTowardsBalance.value = false;
    deductFromBalance.value = 0;
    customerBalance.value = 0;
    customerSearch.value = '';
    showCustomerDropdown.value = false;
    productSearch.value = '';
    showProductDropdown.value = false;
    transactionDate.value = new Date().toISOString().split('T')[0];
    transactionTime.value = new Date().toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: false 
    });
}

function resetForm(): void {
    resetFormData();
    showSuccessModal.value = false;
    saleData.value = null;
}

function closeSuccessModal(): void {
    showSuccessModal.value = false;
    resetForm();
}

function handleKeyboardShortcuts(event: KeyboardEvent): void {
    if (
        event.ctrlKey &&
        event.key === 'Enter' &&
        isCheckoutValid.value
    ) {
        event.preventDefault();
        handleCheckout();
    }
}

function handleClickOutside(event: MouseEvent) {
    const customerDropdown = document.querySelector('.customer-dropdown');
    const productDropdown = document.querySelector('.product-dropdown');
    
    if (customerDropdown && !customerDropdown.contains(event.target as Node)) {
        showCustomerDropdown.value = false;
    }
    
    if (productDropdown && !productDropdown.contains(event.target as Node)) {
        showProductDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeyboardShortcuts);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboardShortcuts);
    document.removeEventListener('click', handleClickOutside);
});

// Watch for checkbox changes to set default deduction amount
watch(payTowardsBalance, (isChecked: boolean) => {
    if (isChecked) {
        // Set default to available change amount, but not more than running balance
        const availableChange = Math.max(
            0,
            amountTendered.value - cartTotalAmount.value,
        );
        const maxDeductible = customerBalance.value;
        deductFromBalance.value = Math.min(availableChange, maxDeductible);
    } else {
        deductFromBalance.value = 0;
    }
});

// Watch for amount tendered changes to update deduction when checkbox is active
watch(amountTendered, () => {
    if (payTowardsBalance.value) {
        const availableChange = Math.max(
            0,
            amountTendered.value - cartTotalAmount.value,
        );
        const maxDeductible = customerBalance.value;
        // Only update if current deduction is more than what's available
        if (
            deductFromBalance.value > Math.min(availableChange, maxDeductible)
        ) {
            deductFromBalance.value = Math.min(availableChange, maxDeductible);
        }
    }
});

</script>

<template>
    <Head title="Create Sale" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 lg:px-8 lg:py-10">
            <!-- Page Header -->
                <div class="mx-auto max-w-7xl">
                    <!-- Alert: No products available -->
                    <div v-if="!props.products || props.products.length === 0" class="mb-6">
                        <div class="alert-primary-subtle">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="alert-primary-icon">
                                        <span class="text-2xl">📦</span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm">No products found</h4>
                                        <p class="mt-1">You need to add products before you can create a sale. Create a product now to get started.</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Button
                                        type="button"
                                        size="lg"
                                        class="rounded-lg shadow-md btn-interactive focus-visible:outline-none"
                                        title="Add product"
                                        @click="router.visit('/products/create')"
                                    >
                                        Add Product
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Layout - 2 Columns on Desktop, 1 Column on Mobile -->
                    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
                        <!-- Left Column - Customer & Payment Info (4 columns on desktop) -->
                        <div class="lg:col-span-4">
                            <!-- Customer & Payment Method Card -->
                            <div
                                class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                            >
                                <div class="space-y-6">
                                    <!-- Customer Selection -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <Label
                                                for="customer"
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >
                                                Customer
                                                <span
                                                    v-if="
                                                        paymentMethod === 'utang' ||
                                                        payTowardsBalance
                                                    "
                                                    class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>
                                        </div>
                                        <div class="relative customer-dropdown">
                                            <div
                                                @click="showCustomerDropdown = !showCustomerDropdown"
                                                class="flex h-10 w-full cursor-pointer items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800"
                                            >
                                                <span class="truncate text-left">
                                                    {{ selectedCustomerName || 'Select customer or walk-in' }}
                                                </span>
                                                <svg class="h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m6 9 6 6 6-6"/>
                                                </svg>
                                            </div>
                                            
                                            <div
                                                v-if="showCustomerDropdown"
                                                    class="absolute z-50 mt-1 max-h-[60vh] w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                            >
                                                <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                                    <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                                    <input
                                                        v-model="customerSearch"
                                                        placeholder="Enter name of customer..."
                                                        class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-[52vh] overflow-auto">
                                                    
                                                    <!-- Customer Options -->
                                                    <div
                                                        v-for="customer in filteredCustomers"
                                                        :key="customer.id"
                                                        @click="selectCustomer(customer.id.toString())"
                                                        class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                                    >
                                                        <div class="flex flex-col">
                                                            <div class="font-medium">{{ customer.name }}</div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-if="filteredCustomers.length === 0"
                                                        class="py-6 text-center text-sm text-muted-foreground"
                                                    >
                                                        <p>No customer found.</p>

                                                        <button
                                                            type="button"
                                                            @click="router.visit('/customers/create')"
                                                            class="inline-flex items-center gap-2 rounded-md bg-primary text-primary-foreground px-3 py-1.5 text-xs font-medium shadow-sm hover:opacity-90 transition btn-interactive"
                                                        >
                                                            <UserPlus class="h-4 w-4" />
                                                            Add Customer
                                                        </button>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Customer Balance Display -->
                                    <div
                                        v-if="selectedCustomer && selectedCustomer.running_utang_balance > 0"
                                        class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-amber-800 dark:text-amber-200"
                                                >Outstanding Balance:</span
                                            >
                                            <span class="text-lg font-bold text-amber-900 dark:text-amber-100"
                                                >{{ formatCurrency(selectedCustomer.running_utang_balance) }}</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <Label
                                            class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >Payment Type</Label
                                        >

                                        <!-- Modern Toggle Design -->
                                        <div
                                            class="relative grid grid-cols-2 gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-700"
                                        >
                                            <!-- Cash Payment Option -->
                                            <button
                                                type="button"
                                                @click="paymentMethod = 'cash'"
                                                :class="[
                                                    'relative flex items-center justify-center rounded-md px-4 py-3 text-sm font-semibold transition-all duration-200',
                                                    paymentMethod === 'cash'
                                                        ? 'border border-blue-200 bg-white text-blue-600 shadow-sm dark:border-blue-700 dark:bg-gray-800 dark:text-blue-400'
                                                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200',
                                                ]"
                                            >
                                                <span class="mr-2">💵</span>
                                                <span>Cash</span>
                                            </button>

                                            <!-- Credit (Utang) Option -->
                                            <button
                                                type="button"
                                                @click="paymentMethod = 'utang'"
                                                :class="[
                                                    'relative flex items-center justify-center rounded-md px-4 py-3 text-sm font-semibold transition-all duration-200',
                                                    paymentMethod === 'utang'
                                                        ? 'border border-orange-200 bg-white text-orange-600 shadow-sm dark:border-orange-700 dark:bg-gray-800 dark:text-orange-400'
                                                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200',
                                                ]"
                                            >
                                                <span class="mr-2">📋</span>
                                                <span>Utang</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Transaction Date & Time -->
                                    <div>
                                        <Label
                                            class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-400"
                                        >
                                            Date & Time
                                        </Label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <Input
                                                    id="transactionDate"
                                                    v-model="transactionDate"
                                                    type="date"
                                                    class="h-10 text-sm text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 focus:border-gray-400 dark:focus:border-gray-500 focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                                />
                                            </div>
                                            <div>
                                                <Input
                                                    id="transactionTime"
                                                    v-model="transactionTime"
                                                    type="time"
                                                    class="h-10 text-sm text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 focus:border-gray-400 dark:focus:border-gray-500 focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Items & Checkout (8 columns on desktop) -->
                        <div class="lg:col-span-8 space-y-6">
                                
                            <!-- Product Selection Dropdown -->
                            <div class="relative product-dropdown">
                                <div
                                    @click="showProductDropdown = !showProductDropdown"
                                    class="flex h-12 w-full cursor-pointer items-center justify-center rounded-md border border-input px-4 py-3 text-medium text-primary dark:hover:bg-white/10 hover:bg-gray-100"
                                >
                                    <Plus class="mr-2 h-4 w-4"/>
                                    Add item to cart
                                </div>
                                
                                <div
                                    v-if="showProductDropdown"
                                    class="absolute z-50 mt-1 max-h-[60vh] w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg dark:border-gray-700"
                                >
                                    <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                        <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                        <input
                                            v-model="productSearch"
                                            placeholder="Enter name of product..."
                                            class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                                            @click.stop
                                        />
                                    </div>
                                    <div class="max-h-[52vh] overflow-auto">
                                        <!-- Product Options -->
                                        <div
                                            v-for="product in filteredProducts"
                                            :key="product.id"
                                            @click="selectProduct(product)"
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                        >
                                            <div class="flex flex-col">
                                                <div class="font-medium">{{ product.product_name }}</div>
                                                <div class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                    {{ formatCurrency(product.unit_price) }}/ <span class="text-gray-500 dark:text-gray-400"> {{ product.unit?.abbreviation || 'unit' }} </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            v-if="filteredProducts.length === 0"
                                            class="py-6 text-center text-sm text-muted-foreground"
                                        >
                                            No products found.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Cart Items -->
                            <div
                                class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                            >
                                <!-- Empty state -->
                                <div v-if="!cartItems.length" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                        <span class="text-3xl">📝</span>
                                    </div>
                                    <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">
                                        No items in cart
                                    </h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Add item to cart to start building your sale
                                    </p>
                                </div>

                                <!-- Items Display -->
                                <div v-else>
                                    <!-- Mobile Layout (lg and below) -->
                                    <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
                                        <div 
                                            v-for="(item, index) in cartItems" 
                                            :key="item.id"
                                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                        >
                                            <div class="space-y-3">
                                                <!-- Header Row -->
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1 min-w-0">
                                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                                            {{ item.product_name }}
                                                        </h3>
                                                        <div class="mt-1 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                            <span>{{ formatCurrency(Number(item.unit_price)) }}/{{ item.unit?.abbreviation || 'unit' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right ml-4">
                                                        <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                                            {{ formatCurrency(calculateItemTotal(item)) }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Controls Row -->
                                                <div class="flex items-center justify-between pt-2">
                                                    <div class="flex items-center gap-2">
                                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            Qty:
                                                        </label>
                                                        <div class="flex items-center gap-1 rounded-md border border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700 p-1">
                                                            <button
                                                                @click="decrementQuantity(index)"
                                                                :disabled="item.quantity <= 0.01"
                                                                class="h-6 w-6 rounded flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent dark:disabled:hover:bg-transparent text-gray-600 dark:text-gray-300"
                                                            >
                                                                <Minus class="h-3 w-3" />
                                                            </button>
                                                            <input
                                                                type="number"
                                                                min="0.01"
                                                                step="0.01"
                                                                :value="item.quantity"
                                                                @input="updateCartItemQuantity(index, parseFloat(($event.target as HTMLInputElement).value))"
                                                                class="w-12 border-0 bg-transparent px-1 py-1 text-center text-sm text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
                                                            />
                                                            <button
                                                                @click="incrementQuantity(index)"
                                                                class="h-6 w-6 rounded flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300"
                                                            >
                                                                <Plus class="h-3 w-3" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <Button 
                                                        size="sm" 
                                                        variant="ghost"
                                                        @click="removeCartItem(index)"
                                                        class="h-8 w-8 p-0 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:text-gray-500 dark:hover:text-red-400 dark:hover:bg-red-950/50"
                                                    >
                                                        <X class="h-3 w-3" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Desktop Table Layout (lg and above) -->
                                    <div class="hidden lg:block">
                                        <table class="w-full">
                                            <thead class="border-b border-gray-200 dark:border-gray-700">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Item
                                                    </th>
                                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Qty
                                                    </th>
                                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Price
                                                    </th>
                                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Amount
                                                    </th>
                                                    <th class="px-4 py-3 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr 
                                                    v-for="(item, index) in cartItems" 
                                                    :key="item.id"
                                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                                >
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ item.product_name }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <div class="flex items-center justify-center gap-1">
                                                            <button
                                                                @click="decrementQuantity(index)"
                                                                :disabled="item.quantity <= 0.01"
                                                                class="h-6 w-6 rounded flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent dark:disabled:hover:bg-transparent text-gray-600 dark:text-gray-300"
                                                            >
                                                                <Minus class="h-3 w-3" />
                                                            </button>
                                                            <input
                                                                type="number"
                                                                min="0.01"
                                                                step="0.01"
                                                                :value="item.quantity"
                                                                @input="updateCartItemQuantity(index, parseFloat(($event.target as HTMLInputElement).value))"
                                                                class="w-12 border border-gray-300 rounded bg-white px-1 py-1 text-center text-sm text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                            />
                                                            <button
                                                                @click="incrementQuantity(index)"
                                                                class="h-6 w-6 rounded flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300"
                                                            >
                                                                <Plus class="h-3 w-3" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ formatCurrency(Number(item.unit_price)) }} / {{ item.unit?.abbreviation || 'unit' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                            {{ formatCurrency(calculateItemTotal(item)) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <Button 
                                                            size="sm" 
                                                            variant="ghost"
                                                            @click="removeCartItem(index)"
                                                            class="h-6 w-6 p-0 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:text-gray-500 dark:hover:text-red-400 dark:hover:bg-red-950/50"
                                                        >
                                                            <X class="h-3 w-3" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Payment Details -->
                            <div
                                v-if="cartItems.length"
                                class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                            >

                                <!-- Payment Summary Section -->
                                <div class="space-y-6">
                                    <!-- Cash Payment Layout -->
                                    <div
                                        v-if="paymentMethod === 'cash'"
                                        class="space-y-6"
                                    >
                                        <!-- Transaction Amounts -->
                                        <div
                                            class="grid grid-cols-1 gap-4 md:grid-cols-3"
                                        >
                                            <!-- Total Amount Display -->
                                            <div class="space-y-2">
                                                <Label
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    >Total Amount</Label
                                                >
                                                <div
                                                    class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20 text-right"
                                                >
                                                    <span
                                                        class="text-2xl font-bold text-orange-700 dark:text-orange-400"
                                                        >{{ formatCurrency(cartTotalAmount) }}</span
                                                    >
                                                </div>
                                            </div>

                                            <!-- Amount Tendered Input -->
                                            <div class="space-y-2">
                                                <Label
                                                    for="amountTendered"
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    >Amount Tendered</Label
                                                >
                                                <div
                                                    class="rounded-lg border border-teal-200 bg-teal-50 p-4 dark:border-teal-800 dark:bg-teal-900/20"
                                                >
                                                    <InputCurrency
                                                        id="amountTendered"
                                                        v-model="amountTendered"
                                                        class="border-0 bg-transparent p-0 text-right text-2xl font-bold text-teal-700 placeholder:text-teal-400 focus:ring-0 dark:text-teal-400 dark:placeholder:text-teal-500"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Change Display -->
                                            <div class="space-y-2">
                                                <Label
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    >Change</Label
                                                >
                                                <div
                                                    class="rounded-lg border p-4 text-right"
                                                    :class="
                                                        changeAmount > 0
                                                            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20'
                                                            : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'
                                                    "
                                                >
                                                    <span
                                                        class="text-2xl font-bold"
                                                        :class="
                                                            changeAmount > 0
                                                                ? 'text-emerald-700 dark:text-emerald-400'
                                                                : 'text-gray-500 dark:text-gray-400'
                                                        "
                                                        >{{ formatCurrency(changeAmount) }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pay towards balance checkbox (only show if customer has running balance) -->
                                        <div
                                            v-if="selectedCustomer && selectedCustomer.running_utang_balance > 0"
                                            class="flex items-center space-x-3 rounded-lg bg-blue-50 border border-blue-200 p-4 dark:bg-blue-900/20 dark:border-blue-800"
                                        >
                                            <Checkbox
                                                id="payTowardsBalance"
                                                v-model="payTowardsBalance"
                                                class="h-5 w-5 rounded-md border-2 border-blue-300 bg-white text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-blue-600 dark:bg-gray-700"
                                            />
                                            <Label
                                                for="payTowardsBalance"
                                                class="cursor-pointer text-sm font-medium text-blue-900 dark:text-blue-100"
                                            >
                                                💡 Gamitin ang sukli para sa utang ng customer
                                            </Label>
                                        </div>

                                        <!-- Balance Deduction Section -->
                                        <div
                                            v-if="payTowardsBalance"
                                            class="grid grid-cols-1 gap-4 rounded-lg border border-blue-200 bg-blue-50 p-4 md:grid-cols-2 dark:border-blue-800 dark:bg-blue-900/20"
                                        >
                                            <div class="space-y-2">
                                                <Label
                                                    for="deductFromBalance"
                                                    class="text-sm font-medium text-blue-800 dark:text-blue-200"
                                                >
                                                    Payment towards Utang
                                                </Label>
                                                    <InputCurrency
                                                        id="deductFromBalance"
                                                        v-model="deductFromBalance"
                                                        min="0"
                                                        :max="
                                                            Math.min(
                                                                selectedCustomer?.running_utang_balance ||
                                                                    0,
                                                                Math.max(
                                                                    0,
                                                                    amountTendered -
                                                                        cartTotalAmount,
                                                                ),
                                                            )
                                                        "
                                                        step="0.01"
                                                        placeholder="0.00"
                                                        class="h-12 text-right text-lg font-semibold"
                                                    />
                                                <p
                                                    class="text-xs text-blue-700 dark:text-blue-300"
                                                >
                                                    Max: {{
                                                        formatCurrency(Math.min(
                                                            selectedCustomer?.running_utang_balance ||
                                                                0,
                                                            Math.max(
                                                                0,
                                                                amountTendered -
                                                                    cartTotalAmount,
                                                            ),
                                                        ))
                                                    }}
                                                </p>
                                            </div>
                                            <div class="space-y-2">
                                                <Label
                                                    class="text-sm font-medium text-blue-800 dark:text-blue-200"
                                                    >New Balance</Label
                                                >
                                                <div
                                                    class="flex h-12 items-center justify-end rounded-lg border border-blue-300 bg-blue-100 p-4 dark:border-blue-600 dark:bg-blue-800/40"
                                                >
                                                    <span
                                                        class="text-lg font-bold text-blue-900 dark:text-blue-100"
                                                    >
                                                        {{
                                                            formatCurrency(
                                                                (selectedCustomer?.running_utang_balance ||
                                                                    0) -
                                                                numericDeductFromBalance
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Utang Payment Layout -->
                                    <div
                                        v-if="paymentMethod === 'utang'"
                                        class="space-y-6"
                                    >
                                        <div
                                            class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                        >
                                            <!-- Total Amount Display -->
                                            <div class="space-y-2">
                                                <Label
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    >Total Amount</Label
                                                >
                                                <div
                                                    class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20"
                                                >
                                                    <span
                                                        class="text-2xl font-bold text-orange-700 dark:text-orange-400"
                                                        >{{
                                                            formatCurrency(cartTotalAmount)
                                                        }}</span
                                                    >
                                                </div>
                                            </div>

                                            <!-- Paid Amount Input -->
                                            <div class="space-y-2">
                                                <Label
                                                    for="paidAmount"
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    >Amount Paid
                                                    <span class="text-red-500"
                                                        >*</span
                                                    ></Label
                                                >
                                                <div
                                                    class="rounded-lg border border-teal-200 bg-teal-50 p-4 dark:border-teal-800 dark:bg-teal-900/20"
                                                >
                                                    <InputCurrency
                                                        id="paidAmount"
                                                        v-model="paidAmount"
                                                        class="border-0 bg-transparent p-0 text-right text-2xl font-bold text-teal-700 placeholder:text-teal-400 focus:ring-0 dark:text-teal-400 dark:placeholder:text-teal-500"
                                                    />
                                                    <p v-if="paidAmount > cartTotalAmount" class="mt-2 text-xs text-red-600 dark:text-red-400">
                                                        Please enter an amount less than the total.
                                                    </p>
                                                    <p v-if="paymentMethod === 'utang' && paidAmount === cartTotalAmount" class="mt-2 text-xs text-yellow-700 dark:text-yellow-300">
                                                        You entered the full amount while using Utang (credit). If you're paying the full amount, please switch the payment type to Cash.
                                                    </p>
                                                </div>
                                                
                                            </div>
                                        </div>

                                        <!-- Balance Display -->
                                        <div
                                            v-if="paidAmount < cartTotalAmount"
                                            class="rounded-lg border border-red-200 bg-red-50 p-5 dark:border-red-800 dark:bg-red-900/20"
                                        >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium text-red-800 dark:text-red-200">
                                                        Total Outstanding Balance:
                                                    </span>
                                                    <span
                                                        class="text-2xl font-bold text-red-700 dark:text-red-400"
                                                        >{{
                                                            formatCurrency(
                                                                (selectedCustomer?.running_utang_balance ||
                                                                    0) +
                                                                (cartTotalAmount -
                                                                    paidAmount)
                                                            )
                                                        }}</span
                                                    >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Checkout Button Section -->
                                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
                                        <Button
                                            size="lg"
                                            class="h-16 w-full bg-green-600 text-xl font-bold text-white shadow-lg hover:bg-green-700 hover:shadow-xl transition-all"
                                            :disabled="!isCheckoutValid || isProcessing"
                                            @click="handleCheckout"
                                        >
                                            <span
                                                v-if="isProcessing"
                                                class="flex items-center justify-center gap-3"
                                            >
                                                <div
                                                    class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                                                ></div>
                                                Processing...
                                            </span>
                                            <span
                                                v-else
                                                class="flex items-center justify-center gap-3"
                                            >
                                                <span>✓</span>
                                                Complete Checkout
                                            </span>
                                        </Button>
                                        <p v-if="(!isCheckoutValid && !isProcessing) && checkoutDisabledReason" class="mt-2 text-sm text-center text-gray-600 dark:text-gray-400">
                                            {{ checkoutDisabledReason }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>

        <!-- Sale Receipt Modal -->
        <SaleReceiptModal
            v-model:open="showSuccessModal"
            :sale-data="saleData"
            @close="closeSuccessModal"
        />
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
