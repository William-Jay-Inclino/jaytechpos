import { Product } from "./pos";

export interface Inventory {
    id: number;
    product_id: number;
    quantity: number;
    low_stock_threshold: number;
    product?: Product | null;
    created_at: string;
    updated_at: string;
}