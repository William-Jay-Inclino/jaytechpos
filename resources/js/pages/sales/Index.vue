<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2, Search } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// Layout & Components
import AddProductModal from '@/components/modals/AddProductModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

// UI Components
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';



// Types
import type { BreadcrumbItem } from '@/types';
import { CartItem, Customer, Product } from '@/types/pos';
import {
    formatManilaDateTime,
    getCurrentManilaDateTime,
} from '@/utils/timezone';

const props = defineProps<{
    products: Product[];
    customers: Customer[];
    sale?: SaleResponse;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'New Sale', href: '/sales' },
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
const isAddModalOpen = ref(false);
const cartItems = ref<CartItem[]>([]);
const selectedCustomerId = ref<string>('0');
const paymentMethod = ref<string>('cash');
const paidAmount = ref(0);
const amountTendered = ref(0);
const payTowardsBalance = ref(false);
const deductFromBalance = ref(0);
const showSuccessModal = ref(false);
const saleData = ref<SaleResponse | null>(null);
const isProcessing = ref(false);

// Customer search state
const customerSearch = ref('');
const showCustomerDropdown = ref(false);


// Business Logic Functions
function addProductToCart(data: { product: Product; quantity: number }): void {
    const { product, quantity } = data;

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
    return Number(item.unit_price) * item.quantity;
}

// Computed Properties
const cartTotalAmount = computed((): number => {
    return cartItems.value.reduce(
        (sum: number, item: CartItem) => sum + calculateItemTotal(item),
        0,
    );
});

const totalItemsInCart = computed((): number => {
    return cartItems.value.reduce(
        (sum: number, item: CartItem) => sum + item.quantity,
        0,
    );
});

const changeAmount = computed((): number => {
    if (payTowardsBalance.value) {
        return Math.max(
            0,
            amountTendered.value -
                cartTotalAmount.value -
                deductFromBalance.value,
        );
    }
    return Math.max(0, amountTendered.value - cartTotalAmount.value);
});

const selectedCustomer = computed((): Customer | null => {
    if (selectedCustomerId.value === '0') return null;
    return (
        props.customers.find(
            (customer) => customer.id.toString() === selectedCustomerId.value,
        ) || null
    );
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return props.customers;
    return props.customers.filter(customer =>
        customer.name.toLowerCase().includes(customerSearch.value.toLowerCase()) ||
        (customer.mobile_number && customer.mobile_number.toLowerCase().includes(customerSearch.value.toLowerCase()))
    );
});

const selectedCustomerName = computed(() => {
    if (selectedCustomerId.value === '0') return 'Walk-in Customer';
    const customer = props.customers.find(c => c.id.toString() === selectedCustomerId.value);
    return customer ? `${customer.name}${customer.mobile_number ? ` (${customer.mobile_number})` : ''}` : '';
});

// Customer data for form display - just use current customer since form is reset after checkout
const displayCustomer = computed((): Customer | null => {
    return selectedCustomer.value;
});

const isCheckoutValid = computed((): boolean => {
    const hasItems = cartItems.value.length > 0;

    if (paymentMethod.value === 'cash') {
        const hasValidCustomer =
            !payTowardsBalance.value || selectedCustomerId.value !== '0';
        const hasEnoughMoney = amountTendered.value >= cartTotalAmount.value;

        if (payTowardsBalance.value) {
            const maxDeductible = Math.min(
                selectedCustomer.value?.running_utang_balance || 0,
                amountTendered.value - cartTotalAmount.value,
            );
            return (
                hasItems &&
                hasValidCustomer &&
                hasEnoughMoney &&
                deductFromBalance.value <= maxDeductible
            );
        }

        return hasItems && hasEnoughMoney;
    } else if (paymentMethod.value === 'utang') {
        return (
            hasItems &&
            selectedCustomerId.value !== '0' &&
            paidAmount.value >= 0
        );
    }

    return false;
});

// Helper text for disabled checkout button
const checkoutHelperText = computed((): string | null => {
    if (isProcessing.value) {
        return 'Processing transaction...';
    }

    if (cartItems.value.length === 0) {
        return 'Add items to your cart to proceed';
    }

    if (paymentMethod.value === 'cash') {
        if (amountTendered.value < cartTotalAmount.value) {
            return `Amount Received (₱${amountTendered.value.toFixed(2)}) must be at least ₱${cartTotalAmount.value.toFixed(2)}`;
        }

        if (payTowardsBalance.value) {
            if (selectedCustomerId.value === '0') {
                return 'Please select a customer to pay towards their balance';
            }

            const maxDeductible = Math.min(
                selectedCustomer.value?.running_utang_balance || 0,
                amountTendered.value - cartTotalAmount.value,
            );

            if (deductFromBalance.value > maxDeductible) {
                return `Payment towards balance cannot exceed ₱${maxDeductible.toFixed(2)}`;
            }
        }
    } else if (paymentMethod.value === 'utang') {
        if (selectedCustomerId.value === '0') {
            return 'Please select a customer for utang transactions';
        }

        if (paidAmount.value < 0) {
            return 'Amount paid cannot be negative';
        }
    }

    return null;
});

// Functions to handle selection
function selectCustomer(customerId: string) {
    selectedCustomerId.value = customerId;
    showCustomerDropdown.value = false;
    customerSearch.value = '';
}

// Event Handlers
function handleCheckout(): void {
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
        deduct_from_balance: payTowardsBalance.value
            ? deductFromBalance.value
            : 0,
    };

    console.log('Processing checkout...', checkoutData);

    router.post('/sales', checkoutData, {
        onSuccess: () => {
            console.log('✅ Sale completed successfully!');
            resetFormData();
        },
        onError: (errors) => {
            console.error('❌ Checkout failed:', errors);
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
}

function resetFormData(): void {
    cartItems.value = [];
    selectedCustomerId.value = '0';
    paymentMethod.value = 'cash';
    paidAmount.value = 0;
    amountTendered.value = 0;
    payTowardsBalance.value = false;
    deductFromBalance.value = 0;
    customerSearch.value = '';
    showCustomerDropdown.value = false;
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
    if (event.ctrlKey && event.key === 'a') {
        event.preventDefault();
        isAddModalOpen.value = true;
    } else if (
        event.ctrlKey &&
        event.key === 'Enter' &&
        isCheckoutValid.value
    ) {
        event.preventDefault();
        handleCheckout();
    }
}

// Handle click outside to close dropdowns
function handleClickOutside(event: MouseEvent) {
    const customerDropdown = document.querySelector('.customer-dropdown');
    if (customerDropdown && !customerDropdown.contains(event.target as Node)) {
        showCustomerDropdown.value = false;
    }
}

// Add keyboard event listener when component mounts
onMounted(() => {
    document.addEventListener('keydown', handleKeyboardShortcuts);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboardShortcuts);
    document.removeEventListener('click', handleClickOutside);
});

// Watch for sale prop changes to show success modal
watch(
    () => props.sale,
    (newSale: SaleResponse | undefined) => {
        if (newSale) {
            console.log('✅ Sale completed successfully!', newSale);
            saleData.value = newSale;
            showSuccessModal.value = true;
            isProcessing.value = false;
        }
    },
    { immediate: true },
);

// Watch for checkbox changes to set default deduction amount
watch(payTowardsBalance, (isChecked: boolean) => {
    console.log('payTowardsBalance changed:', isChecked);
    if (isChecked) {
        // Set default to available change amount, but not more than running balance
        const availableChange = Math.max(
            0,
            amountTendered.value - cartTotalAmount.value,
        );
        const maxDeductible =
            selectedCustomer.value?.running_utang_balance || 0;
        deductFromBalance.value = Math.min(availableChange, maxDeductible);
        console.log('Setting deductFromBalance to:', deductFromBalance.value);
    } else {
        deductFromBalance.value = 0;
        console.log('Reset deductFromBalance to 0');
    }
});

// Watch for amount tendered changes to update deduction when checkbox is active
watch(amountTendered, () => {
    if (payTowardsBalance.value) {
        const availableChange = Math.max(
            0,
            amountTendered.value - cartTotalAmount.value,
        );
        const maxDeductible =
            selectedCustomer.value?.running_utang_balance || 0;
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

                <!-- Main Layout - 2 Columns on Desktop, 1 Column on Mobile -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
                    <!-- Left Column - Customer & Payment Info (4 columns on desktop) -->
                    <div class="lg:col-span-4">
                        <!-- Customer & Payment Method Card -->
                        <div
                            class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                        >
                            <div class="space-y-6">
                                <!-- Customer Selection -->
                                <div>
                                    <Label
                                        for="customer"
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
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
                                        <span v-else class="text-gray-500"
                                            >(Optional)</span
                                        >
                                    </Label>
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
                                            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            <div class="flex items-center border-b px-3 pb-2 mb-2 dark:border-gray-700">
                                                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                                <input
                                                    v-model="customerSearch"
                                                    placeholder="Search customers by name or mobile number..."
                                                    class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 dark:text-white"
                                                    @click.stop
                                                />
                                            </div>
                                            <div class="max-h-40 overflow-auto">
                                                <!-- Walk-in Customer Option -->
                                                <div
                                                    @click="selectCustomer('0')"
                                                    class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                                >
                                                    <div class="flex flex-col">
                                                        <div class="font-medium">Walk-in Customer</div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            No customer information required
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Customer Options -->
                                                <div
                                                    v-for="customer in filteredCustomers"
                                                    :key="customer.id"
                                                    @click="selectCustomer(customer.id.toString())"
                                                    class="relative flex cursor-default select-none items-center rounded-sm px-2 py-2.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                                >
                                                    <div class="flex flex-col">
                                                        <div class="font-medium">{{ customer.name }}</div>
                                                        <div 
                                                            v-if="customer.mobile_number"
                                                            class="text-xs text-gray-500 dark:text-gray-400"
                                                        >
                                                            {{ customer.mobile_number }}
                                                        </div>
                                                        <div 
                                                            v-if="customer.running_utang_balance && customer.running_utang_balance > 0"
                                                            class="text-xs text-red-600 dark:text-red-400 font-medium"
                                                        >
                                                            Balance: ₱{{ customer.running_utang_balance.toFixed(2) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="filteredCustomers.length === 0"
                                                    class="py-6 text-center text-sm text-muted-foreground"
                                                >
                                                    No customers found.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Balance Display -->
                                <div
                                    v-if="
                                        displayCustomer &&
                                        displayCustomer.running_utang_balance >
                                            0
                                    "
                                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm font-medium text-amber-800 dark:text-amber-200"
                                            >Outstanding Balance:</span
                                        >
                                        <span
                                            class="text-lg font-bold text-amber-900 dark:text-amber-100"
                                            >₱{{
                                                displayCustomer.running_utang_balance.toFixed(
                                                    2,
                                                )
                                            }}</span
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
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Items & Checkout (8 columns on desktop) -->
                    <div class="lg:col-span-8">
                        <!-- Items & Payment Section -->
                        <div
                            class="rounded-xl border border-gray-300 bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:ring-gray-800 dark:shadow-none"
                        >
                            <div class="mb-6 flex items-center justify-between">
                                <Button
                                    size="lg"
                                    @click="isAddModalOpen = true"
                                >
                                    Add Item
                                </Button>
                            </div>

                            <!-- Items List -->
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                                <!-- Empty state -->
                                <div v-if="!cartItems.length" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                        <span class="text-3xl">📝</span>
                                    </div>
                                    <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">
                                        No items in cart
                                    </h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Click "Add Item" to start building your sale
                                    </p>
                                </div>

                                <!-- Modern Product Cards -->
                                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <div 
                                        v-for="(item, index) in cartItems" 
                                        :key="item.id"
                                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <!-- Mobile Layout (lg and below) -->
                                        <div class="block xl:hidden space-y-3">
                                            <!-- Header Row -->
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                                        {{ item.product_name }}
                                                    </h3>
                                                    <div class="mt-1 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                        <span>₱{{ Number(item.unit_price).toFixed(2) }}/unit</span>
                                                    </div>
                                                </div>
                                                <div class="text-right ml-4">
                                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                                        ₱{{ calculateItemTotal(item).toFixed(2) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Controls Row -->
                                            <div class="flex items-center justify-between pt-2">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Qty:
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        :value="item.quantity"
                                                        @input="updateCartItemQuantity(index, parseInt(($event.target as HTMLInputElement).value))"
                                                        class="w-16 rounded-md border border-gray-300 bg-white px-2 py-1 text-center text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    />
                                                </div>
                                                
                                                <Button 
                                                    size="sm" 
                                                    variant="destructive"
                                                    @click="removeCartItem(index)"
                                                    class="h-8 px-3"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </div>

                                        <!-- Desktop Layout (xl and above) -->
                                        <div class="hidden xl:flex xl:items-center xl:justify-between">
                                            <div class="flex items-center space-x-4 flex-1">
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                                        {{ item.product_name }}
                                                    </h3>
                                                </div>
                                                <div class="flex items-center space-x-6">
                                                    <div class="flex items-center gap-2">
                                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            Qty:
                                                        </label>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            :value="item.quantity"
                                                            @input="updateCartItemQuantity(index, parseInt(($event.target as HTMLInputElement).value))"
                                                            class="w-16 rounded-md border border-gray-300 bg-white px-2 py-1 text-center text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                        />
                                                    </div>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400 min-w-[80px] text-right">
                                                        ₱{{ Number(item.unit_price).toFixed(2) }}/unit
                                                    </span>
                                                    <div class="text-lg font-bold text-green-600 dark:text-green-400 min-w-[100px] text-right">
                                                        ₱{{ calculateItemTotal(item).toFixed(2) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 ml-6">
                                                <Button 
                                                    size="sm" 
                                                    variant="destructive"
                                                    @click="removeCartItem(index)"
                                                >
                                                    <Trash2 class="h-4 w-4 mr-2" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Summary Section -->
                            <div v-if="cartItems.length" class="mt-8 space-y-6">
                                <div class="pt-6">

                            <!-- Cash Payment Layout -->
                            <div
                                v-if="paymentMethod === 'cash'"
                                class="space-y-6"
                            >
                                <!-- Pay towards balance checkbox (only show if customer has running balance) -->
                                <div
                                    v-if="
                                        selectedCustomer &&
                                        selectedCustomer.running_utang_balance >
                                            0
                                    "
                                    class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700"
                                >
                                    <Checkbox
                                        id="payTowardsBalance"
                                        v-model="payTowardsBalance"
                                    />
                                    <Label
                                        for="payTowardsBalance"
                                        class="cursor-pointer text-sm font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        Gamitin ang sukli para sa utang ng customer
                                    </Label>
                                </div>

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
                                            class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20"
                                        >
                                            <span
                                                class="text-2xl font-bold text-orange-700 dark:text-orange-400"
                                                >₱{{
                                                    cartTotalAmount.toFixed(2)
                                                }}</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Amount Tendered Input -->
                                    <div class="space-y-2">
                                        <Label
                                            for="amountTendered"
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >Amount Received</Label
                                        >
                                        <div
                                            class="rounded-lg border border-teal-200 bg-teal-50 p-4 dark:border-teal-800 dark:bg-teal-900/20"
                                        >
                                            <Input
                                                id="amountTendered"
                                                v-model.number="amountTendered"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
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
                                            class="rounded-lg border p-4"
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
                                                >₱{{
                                                    changeAmount.toFixed(2)
                                                }}</span
                                            >
                                        </div>
                                    </div>
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
                                        <Input
                                            id="deductFromBalance"
                                            v-model.number="deductFromBalance"
                                            type="number"
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
                                            Max: ₱{{
                                                Math.min(
                                                    selectedCustomer?.running_utang_balance ||
                                                        0,
                                                    Math.max(
                                                        0,
                                                        amountTendered -
                                                            cartTotalAmount,
                                                    ),
                                                ).toFixed(2)
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
                                                ₱{{
                                                    (
                                                        (selectedCustomer?.running_utang_balance ||
                                                            0) -
                                                        deductFromBalance
                                                    ).toFixed(2)
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
                                                >₱{{
                                                    cartTotalAmount.toFixed(2)
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
                                        <Input
                                            id="paidAmount"
                                            v-model.number="paidAmount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="h-12 border-2 text-right text-lg font-semibold focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>

                                <!-- Balance Display -->
                                <div
                                    v-if="paidAmount < cartTotalAmount"
                                    class="space-y-2"
                                >
                                    <Label
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >New Balance After Transaction</Label
                                    >
                                    <div
                                        class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20"
                                    >
                                        <span
                                            class="text-xl font-bold text-red-700 dark:text-red-400"
                                            >₱{{
                                                (
                                                    (selectedCustomer?.running_utang_balance ||
                                                        0) +
                                                    (cartTotalAmount -
                                                        paidAmount)
                                                ).toFixed(2)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Checkout Button Section -->
                            <div v-if="cartItems.length" class="mt-8 space-y-4">
                                <Button
                                    size="lg"
                                    class="h-14 w-full bg-green-600 text-lg font-bold text-white shadow-lg hover:bg-green-700"
                                    :disabled="!isCheckoutValid || isProcessing"
                                    @click="handleCheckout"
                                >
                                    <span
                                        v-if="isProcessing"
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                                        ></div>
                                        Processing...
                                    </span>
                                    <span
                                        v-else
                                        class="flex items-center gap-2"
                                    >
                                        ✅ Complete Checkout
                                    </span>
                                </Button>

                                <!-- Helper Text -->
                                <div
                                    v-if="checkoutHelperText"
                                    class="text-center"
                                >
                                    <p
                                        class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ checkoutHelperText }}
                                    </p>
                                </div>
                            </div>
                        </div>

                                                        </div>
                            </div>
                        </div>

                        <!-- Add Product Modal -->
                        <AddProductModal
                            v-model:open="isAddModalOpen"
                            :products="products"
                            @add="addProductToCart"
                        />
                    </div>
                </div>
            </div>

        <!-- Success Modal with Receipt -->
        <Dialog v-model:open="showSuccessModal">
            <DialogContent class="max-h-[85vh] max-w-lg flex flex-col">
                <DialogHeader class="pb-2 text-center flex-shrink-0">
                    <DialogTitle
                        class="flex items-center justify-center gap-2 text-xl text-green-600 dark:text-green-400"
                    >
                        <span class="text-2xl">✅</span>
                        Sale Completed!
                    </DialogTitle>
                    <DialogDescription class="text-sm text-muted-foreground">
                        Transaction processed successfully
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 space-y-5 overflow-y-auto min-h-0">
                    <!-- Receipt Header -->
                    <div
                        class="space-y-2 rounded-lg bg-muted/30 p-4 text-center"
                    >
                        <h3 class="text-lg font-bold">JTech POS</h3>
                        <p class="text-sm text-muted-foreground">
                            Point of Sale Receipt
                        </p>
                    </div>

                    <!-- Receipt Content -->
                    <div id="receipt-content" class="space-y-4">
                        <!-- Receipt Details -->
                        <div v-if="saleData" class="space-y-4">
                            <!-- Basic Info -->
                            <div
                                class="space-y-2 rounded-lg bg-muted/20 p-3 text-sm"
                            >
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Invoice #:</span
                                    >
                                    <span class="font-mono font-medium">{{
                                        saleData.invoice_number
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Date:</span
                                    >
                                    <span class="font-medium">{{
                                        formatManilaDateTime(
                                            saleData.transaction_date,
                                        )
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Customer:</span
                                    >
                                    <span class="font-medium">{{
                                        saleData.customer_name ||
                                        'Walk-in Customer'
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Cashier:</span
                                    >
                                    <span class="font-medium">{{
                                        saleData.cashier
                                    }}</span>
                                </div>
                            </div>

                            <!-- Items -->
                            <div class="space-y-3">
                                <h4
                                    class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Items Sold
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="item in saleData.items"
                                        :key="item.id"
                                        class="flex items-center justify-between border-b border-border/50 py-2 last:border-0"
                                    >
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">
                                                {{ item.product_name }}
                                            </div>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ item.quantity }} × ₱{{
                                                    item.unit_price.toFixed(2)
                                                }}
                                            </div>
                                        </div>
                                        <div class="text-sm font-medium">
                                            ₱{{ item.total_amount.toFixed(2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total -->
                            <div
                                class="space-y-3 rounded-lg bg-muted/30 p-4 text-center"
                            >
                                <div class="mb-1 text-xs text-muted-foreground">
                                    Total Amount
                                </div>
                                <div class="text-2xl font-bold">
                                    ₱{{ saleData.total_amount.toFixed(2) }}
                                </div>
                            </div>

                            <!-- Payment Details Section -->
                            <div class="space-y-2 rounded-lg bg-muted/20 p-3">
                                <h4
                                    class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Payment Details
                                </h4>

                                <!-- Cash Payment Details -->
                                <div
                                    v-if="saleData.payment_type === 'cash'"
                                    class="space-y-1 text-sm"
                                >
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >Payment Method:</span
                                        >
                                        <span class="font-medium">💵 Cash</span>
                                    </div>
                                    <div
                                        v-if="saleData.amount_tendered"
                                        class="flex justify-between"
                                    >
                                        <span class="text-muted-foreground"
                                            >Amount Received:</span
                                        >
                                        <span
                                            >₱{{
                                                saleData.amount_tendered.toFixed(
                                                    2,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="
                                            saleData.change_amount !==
                                                undefined &&
                                            saleData.change_amount !== null
                                        "
                                        class="flex justify-between"
                                    >
                                        <span class="text-muted-foreground"
                                            >Change:</span
                                        >
                                        <span
                                            >₱{{
                                                (
                                                    saleData.change_amount || 0
                                                ).toFixed(2)
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="
                                            saleData.balance_payment &&
                                            saleData.balance_payment > 0
                                        "
                                        class="flex justify-between"
                                    >
                                        <span class="text-muted-foreground"
                                            >Payment Towards Balance:</span
                                        >
                                        <span
                                            >₱{{
                                                (
                                                    saleData.balance_payment ||
                                                    0
                                                ).toFixed(2)
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Utang Payment Details -->
                                <div
                                    v-else-if="
                                        saleData.payment_type === 'utang'
                                    "
                                    class="space-y-1 text-sm"
                                >
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >Payment Method:</span
                                        >
                                        <span class="font-medium"
                                            >📝 Credit (Utang)</span
                                        >
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >Amount Paid:</span
                                        >
                                        <span
                                            >₱{{
                                                saleData.paid_amount.toFixed(2)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Balance (only if customer involved) -->
                            <div
                                v-if="saleData.customer_id"
                                class="space-y-2 rounded-lg bg-muted/20 p-3"
                            >
                                <h4
                                    class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Customer Balance
                                </h4>
                                <div class="space-y-1 text-sm">
                                    <div
                                        v-if="
                                            saleData.original_customer_balance !==
                                            undefined
                                        "
                                        class="flex justify-between"
                                    >
                                        <span class="text-muted-foreground"
                                            >Previous:</span
                                        >
                                        <span
                                            >₱{{
                                                (
                                                    saleData.original_customer_balance ||
                                                    0
                                                ).toFixed(2)
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between border-t pt-1 font-medium"
                                    >
                                        <span>New Balance:</span>
                                        <span
                                            :class="
                                                (saleData.new_customer_balance ||
                                                    0) > 0
                                                    ? 'text-destructive'
                                                    : ''
                                            "
                                        >
                                            ₱{{
                                                (
                                                    saleData.new_customer_balance ||
                                                    0
                                                ).toFixed(2)
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Message -->
                            <div class="rounded-lg bg-muted/30 p-3 text-center">
                                <p class="text-sm font-medium">
                                    Thank you for your business!
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        formatManilaDateTime(
                                            getCurrentManilaDateTime(),
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-center border-t pt-4 flex-shrink-0">
                    <Button
                        @click="closeSuccessModal"
                        class="bg-green-600 px-8 text-white hover:bg-green-700"
                    >
                        Close & New Sale
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
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
