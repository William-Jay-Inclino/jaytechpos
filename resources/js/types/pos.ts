import { User } from ".";

export interface Customer {
    id: number;
    name: string;
    mobile_number: string;
}

export interface PaymentMethod {
    id: number;
    name: string;
    description: string;
    is_active: boolean;
}

export interface SalesStatus {
    id: number;
    name: string;
    description: string;
    is_active: boolean; 
}

export interface Sale {
    id: number;
    user_id: number;
    customer_id: number;
    payment_method_id: number;
    sales_status_id: number;
    total_amount: number;
    discount_amount: number;
    vat_amount: number;
    net_amount: number;
    created_at: string;
    updated_at: string;

    // relationships
    customer: Customer;
    user: User;
    payment_method: PaymentMethod;
    sales_status: SalesStatus;
}