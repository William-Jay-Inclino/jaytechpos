import { User } from ".";

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

export interface Sale {
    id: number;
    user_id: number;
    customer_id: number;
    total_amount: number;
    vat_amount: number;
    net_amount: number;
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
    supplier_id: number;
    unit_id: number;
    vat_type: 'vat' | 'vat_exempt' | 'vat_zero_rated' | 'non_vat';
    sku: string;
    barcode: string | null;
    product_name: string;
    description: string;
    unit_price: number;
    cost_price: number;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;

    // relationships
    category?: ProductCategory;
    supplier?: Supplier;
    unit?: Unit;
}