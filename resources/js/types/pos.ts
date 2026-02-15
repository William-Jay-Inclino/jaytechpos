import { Inventory } from "./inventory";

export interface Customer {
    id: number;
    user_id: number;
    name: string;
    mobile_number: string;
    remarks: string | null;
    has_utang: boolean;
    interest_rate: number | null;
    effective_interest_rate: number;
    running_utang_balance: number;
    created_at: string;
    updated_at: string;
}

export interface CustomerTransaction {
    id: number;
    customer_id: number;
    transaction_type: 'sale' | 'utang_payment' | 'monthly_interest' | 'starting_balance' | 'balance_update';
    reference_id: number | null;
    previous_balance: string;
    new_balance: string;
    transaction_desc: string;
    transaction_date: string;
    transaction_amount: string;
}

export interface Sale {
    id: number;
    invoice_number: string;
    transaction_date: string;
    customer_id: number;
    total_amount: number;
    paid_amount: number;
    amount_tendered: number;
    deduct_from_balance: number;
    change_amount: number;
    original_customer_balance: number;
    new_customer_balance: number;
    payment_type: 'cash' | 'utang';
    notes: string | null;
    customer_name: string 
    cashier: string
    created_at: string;
    items: SaleItem[];
}

export interface SaleItem {
    id: number;
    product_id: number;
    product_name: string;
    quantity: number;
    unit_price: number;
    total_amount: number;
}

export interface CartItem extends Product {
    quantity: number;
}

export interface Unit {
    id: number;
    unit_name: string;
    abbreviation: string;
    created_at: string;
    updated_at: string;

    // relationships
    products?: Product[];
}

export interface Product {
    id: number;
    user_id: number;
    unit_id: number;
    product_name: string;
    barcode: string | null;
    description: string;
    unit_price: number;
    cost_price: number;
    status: 'active' | 'inactive';
    inventory: Inventory;
    created_at: string;
    updated_at: string;

    // relationships
    unit?: Unit;
}
