import { User } from ".";
import { Product } from "./inventory";

export interface Customer {
    id: number;
    name: string;
    mobile_number: string;
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