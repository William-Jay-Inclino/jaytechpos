export interface Category {
    id: number;
    category_name: string;
    description: string;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;

    // relationships
    products?: Product[];
}

export interface Supplier {
    id: number;
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
    category?: Category;
    supplier?: Supplier;
    unit?: Unit;
    vat?: VatRate;
    inventory_level?: InventoryLevel;
    stock_movements?: StockMovement[];
}

export interface InventoryLevel {
    id: number;
    product_id: number;
    quantity: number;
    reorder_level: number;
    created_at: string;
    updated_at: string;

    // relationships
    product?: Product;
}

export interface StockMovement {
    id: number;
    product_id: number;
    quantity: number;
    movement_type: 'stock_in' | 'stock_out' | 'adjustment';
    reference: string;
    batch_number: string | null;
    expiry_date: string | null;
    remarks: string | null;
    created_at: string;
    updated_at: string;

    // relationships
    product?: Product;
}