import { User } from '.';

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
    type: 'sale' | 'utang_payment' | 'monthly_interest';
    date: string;
    amount: number;
    formatted_amount: string;
    description: string;
    notes?: string;
    interest_rate?: number;
    invoice_number?: string;
    payment_type?: 'cash' | 'utang';
    total_amount?: number;
    paid_amount?: number;
    previous_balance?: number;
    new_balance?: number;
    formatted_previous_balance?: string;
    formatted_new_balance?: string;
    computation_date?: string;
    sales_items?: Array<{
        id: number;
        product_name: string;
        quantity: number;
        unit_price: number;
        total_price: number;
    }>;
}

export interface Sale {
    id: number;
    user_id: number;
    customer_id: number;
    total_amount: number;
    paid_amount: number;
    previous_balance: number;
    new_balance: number;
    invoice_number: string;
    payment_type: 'cash' | 'utang';
    transaction_date: string;
    notes: string | null;
    created_at: string;
    updated_at: string;

    // relationships
    customer: Customer;
    user: User;
}

export interface SaleItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
}

export interface CartItem extends Product {
    quantity: number;
}

export interface ProductCategory {
    id: number;
    user_id: number;
    name: string;
    description: string;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;

    // relationships
    products?: Product[];
}

export interface Supplier {
    id: number;
    user_id: number;
    supplier_name: string;
    contact_name: string;
    address: string;
    phone: string;
    email: string;
    created_at: string;
    updated_at: string;

    // relationships
    products?: Product[];
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

export interface VatRate {
    id: number;
    rate_name: string;
    rate_percentage: number;
    effective_date: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // relationships
    products?: Product[];
}

export interface Product {
    id: number;
    user_id: number;
    category_id: number;
    unit_id: number;
    product_name: string;
    description: string;
    unit_price: number;
    cost_price: number;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;

    // relationships
    product_category?: ProductCategory;
    unit?: Unit;
}
