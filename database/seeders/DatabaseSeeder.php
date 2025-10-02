<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {   

        // SEED UNITS - atleast 10 units
        DB::table('units')->insert([
            ['unit_name' => 'Piece', 'abbreviation' => 'pc/pcs'],
            ['unit_name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['unit_name' => 'Gram', 'abbreviation' => 'g'],
            ['unit_name' => 'Liter', 'abbreviation' => 'L'],
            ['unit_name' => 'Milliliter', 'abbreviation' => 'mL'],
            ['unit_name' => 'Pack', 'abbreviation' => 'pk'],
            ['unit_name' => 'Box', 'abbreviation' => 'bx'],
            ['unit_name' => 'Dozen', 'abbreviation' => 'dz'],
            ['unit_name' => 'Pound', 'abbreviation' => 'lb'],
            ['unit_name' => 'Ounce', 'abbreviation' => 'oz'],
        ]);

        // SEED CUSTOMER - atleast 5 customers
        DB::table('customers')->insert([
            ['name' => 'Alice Johnson', 'mobile_number' => '09171234567'],
            ['name' => 'Bob Smith', 'mobile_number' => '09179876543'],
            ['name' => 'Charlie Brown', 'mobile_number' => '09281234567'],
            ['name' => 'Diana Prince', 'mobile_number' => '09381234567'],
            ['name' => 'Ethan Hunt', 'mobile_number' => '09481234567'],
        ]);

        // SEED SALES STATUSES - atleast 3 statuses
        DB::table('sales_statuses')->insert([
            ['name' => 'Completed', 'description' => 'Sale completed successfully', 'is_active' => true],
            ['name' => 'Refunded', 'description' => 'Sale has been refunded', 'is_active' => true],
            ['name' => 'Voided', 'description' => 'Sale was voided', 'is_active' => false],
        ]);

        // SEED VAT RATES - atleast 3 rates
        DB::table('vat_rates')->insert([
            ['rate_name' => 'VAT 12%', 'rate_percentage' => 12.00],
            ['rate_name' => 'VAT 10%', 'rate_percentage' => 5.00],
            ['rate_name' => 'VAT 0%', 'rate_percentage' => 0.00],
        ]);
        
        // SEED CATEGORIES - context mini grocery store atleast 15 categories. Group by type of products
        DB::table('categories')->insert([
            ['category_name' => 'Beverages', 'description' => 'Drinks and refreshments'],
            ['category_name' => 'Snacks', 'description' => 'Chips, cookies, and other snacks'],
            ['category_name' => 'Dairy Products', 'description' => 'Milk, cheese, yogurt, etc.'],
            ['category_name' => 'Bakery', 'description' => 'Bread, pastries, and baked goods'],
            ['category_name' => 'Fruits & Vegetables', 'description' => 'Fresh produce'],
            ['category_name' => 'Meat & Poultry', 'description' => 'Fresh and processed meats'],
            ['category_name' => 'Seafood', 'description' => 'Fresh and frozen fish and seafood'],
            ['category_name' => 'Frozen Foods', 'description' => 'Frozen meals and ingredients'],
            ['category_name' => 'Canned Goods', 'description' => 'Canned vegetables, soups, etc.'],
            ['category_name' => 'Dry Goods', 'description' => 'Rice, pasta, beans, etc.'],
            ['category_name' => 'Condiments & Sauces', 'description' => 'Ketchup, mustard, sauces, etc.'],
            ['category_name' => 'Household Supplies', 'description' => 'Cleaning products and supplies'],
            ['category_name' => 'Personal Care', 'description' => 'Toiletries and personal care items'],
            ['category_name' => 'Health & Wellness', 'description' => 'Vitamins and supplements'],
            ['category_name' => 'Baby Products', 'description' => 'Diapers, baby food, etc.'],
        ]);
        

        if (App::environment('local')) {

            // SEED SUPPLIERS - atleast 10 suppliers
            DB::table('suppliers')->insert([
                ['supplier_name' => 'Fresh Farms', 'contact_name' => 'John Doe', 'address' => '123 Farm Lane', 'phone' => '555-1234', 'email' => 'contact@freshfarms.com'],
                ['supplier_name' => 'Grocery Wholesale', 'contact_name' => 'Jane Smith', 'address' => '456 Market St', 'phone' => '555-5678', 'email' => 'contact@grocerywholesale.com'],
                ['supplier_name' => 'Local Produce Co.', 'contact_name' => 'Alice Johnson', 'address' => '789 Orchard Ave', 'phone' => '555-8765', 'email' => 'contact@localproduce.com'],
                ['supplier_name' => 'Dairy Delights', 'contact_name' => 'Bob Brown', 'address' => '321 Dairy Rd', 'phone' => '555-4321', 'email' => 'contact@dairydelights.com'],
                ['supplier_name' => 'Meat Masters', 'contact_name' => 'Charlie White', 'address' => '654 Butcher Blvd', 'phone' => '555-2468', 'email' => 'contact@meatmasters.com'],
                ['supplier_name' => 'Seafood Shack', 'contact_name' => 'Diana Green', 'address' => '987 Ocean Dr', 'phone' => '555-1357', 'email' => 'contact@seafoodshack.com'],
                ['supplier_name' => 'Bakery Bliss', 'contact_name' => 'Eve Black', 'address' => '159 Bread St', 'phone' => '555-8642', 'email' => 'contact@bakerybliss.com'],
                ['supplier_name' => 'Snack Attack', 'contact_name' => 'Frank Blue', 'address' => '753 Snack Ave', 'phone' => '555-7531', 'email' => 'contact@snackattack.com'],
                ['supplier_name' => 'Beverage Hub', 'contact_name' => 'Grace Red', 'address' => '951 Drink Blvd', 'phone' => '555-1597', 'email' => 'contact@beveragehub.com'],
                ['supplier_name' => 'Frozen Foods Inc.', 'contact_name' => 'Hank Yellow', 'address' => '357 Ice Cream Rd', 'phone' => '555-3579', 'email' => 'contact@frozenfoods.com'],
            ]);
    
            // SEED PRODUCTS - atleast 40 products
            DB::table('products')->insert([
                ['id' => 1, 'sku' => 'BEV-0001', 'product_name' => 'Orange Juice', 'category_id' => 1, 'supplier_id' => 9, 'unit_id' => 4, 'vat_id' => 1, 'cost_price' => 85.00, 'unit_price' => 95.00],
                ['id' => 2, 'sku' => 'BEV-0002', 'product_name' => 'Milk', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 4, 'vat_id' => 1, 'cost_price' => 60.00, 'unit_price' => 70.00],
                ['id' => 3, 'sku' => 'BEV-0003', 'product_name' => 'Cola Drink', 'category_id' => 1, 'supplier_id' => 9, 'unit_id' => 4, 'vat_id' => 1, 'cost_price' => 25.00, 'unit_price' => 30.00],
                ['id' => 4, 'sku' => 'BEV-0004', 'product_name' => 'Energy Drink', 'category_id' => 1, 'supplier_id' => 9, 'unit_id' => 4, 'vat_id' => 1, 'cost_price' => 45.00, 'unit_price' => 55.00],

                ['id' => 5, 'sku' => 'SNK-0001', 'product_name' => 'Chocolate Chip Cookies', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 90.00, 'unit_price' => 110.00],
                ['id' => 6, 'sku' => 'SNK-0002', 'product_name' => 'Potato Chips', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 7, 'sku' => 'SNK-0003', 'product_name' => 'Granola Bars', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 8, 'sku' => 'SNK-0004', 'product_name' => 'Pretzels', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 40.00, 'unit_price' => 50.00],

                ['id' => 9, 'sku' => 'DAI-0001', 'product_name' => 'Cheddar Cheese', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 2, 'vat_id' => 1, 'cost_price' => 180.00, 'unit_price' => 200.00],
                ['id' => 10, 'sku' => 'DAI-0002', 'product_name' => 'Yogurt', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 11, 'sku' => 'DAI-0003', 'product_name' => 'Butter', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 150.00, 'unit_price' => 165.00],

                ['id' => 12, 'sku' => 'BAK-0001', 'product_name' => 'Whole Wheat Bread', 'category_id' => 4, 'supplier_id' => 7, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 45.00, 'unit_price' => 55.00],
                ['id' => 13, 'sku' => 'BAK-0002', 'product_name' => 'Bagels', 'category_id' => 4, 'supplier_id' => 7, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 50.00, 'unit_price' => 65.00],

                ['id' => 14, 'sku' => 'FRU-0001', 'product_name' => 'Apple', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_id' => 3, 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 15, 'sku' => 'FRU-0002', 'product_name' => 'Banana', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_id' => 3, 'cost_price' => 60.00, 'unit_price' => 75.00],
                ['id' => 16, 'sku' => 'FRU-0003', 'product_name' => 'Grapes', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_id' => 3, 'cost_price' => 180.00, 'unit_price' => 200.00],
                ['id' => 17, 'sku' => 'FRU-0004', 'product_name' => 'Strawberries', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_id' => 3, 'cost_price' => 200.00, 'unit_price' => 230.00],

                ['id' => 18, 'sku' => 'MEP-0001', 'product_name' => 'Chicken Breast', 'category_id' => 6, 'supplier_id' => 5, 'unit_id' => 2, 'vat_id' => 1, 'cost_price' => 180.00, 'unit_price' => 200.00],
                ['id' => 19, 'sku' => 'MEP-0002', 'product_name' => 'Turkey Slices', 'category_id' => 6, 'supplier_id' => 5, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 150.00, 'unit_price' => 170.00],

                ['id' => 20, 'sku' => 'SEA-0001', 'product_name' => 'Salmon Fillet', 'category_id' => 7, 'supplier_id' => 6, 'unit_id' => 2, 'vat_id' => 1, 'cost_price' => 400.00, 'unit_price' => 450.00],
                ['id' => 21, 'sku' => 'SEA-0002', 'product_name' => 'Shrimp', 'category_id' => 7, 'supplier_id' => 6, 'unit_id' => 2, 'vat_id' => 1, 'cost_price' => 350.00, 'unit_price' => 400.00],

                ['id' => 22, 'sku' => 'FF-0001', 'product_name' => 'Frozen Pizza', 'category_id' => 8, 'supplier_id' => 10, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 220.00, 'unit_price' => 250.00],
                ['id' => 23, 'sku' => 'FF-0002', 'product_name' => 'Ice Cream', 'category_id' => 8, 'supplier_id' => 10, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 150.00, 'unit_price' => 180.00],

                ['id' => 24, 'sku' => 'CAG-0001', 'product_name' => 'Canned Beans', 'category_id' => 9, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 25, 'sku' => 'CAG-0002', 'product_name' => 'Tomato Soup', 'category_id' => 9, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 40.00, 'unit_price' => 50.00],
                ['id' => 26, 'sku' => 'CAG-0003', 'product_name' => 'Black Beans', 'category_id' => 9, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 38.00, 'unit_price' => 48.00],

                ['id' => 27, 'sku' => 'DRG-0001', 'product_name' => 'Rice', 'category_id' => 10, 'supplier_id' => 2, 'unit_id' => 2, 'vat_id' => 3, 'cost_price' => 1800.00, 'unit_price' => 2000.00],
                ['id' => 28, 'sku' => 'DRG-0002', 'product_name' => 'Pasta', 'category_id' => 10, 'supplier_id' => 2, 'unit_id' => 2, 'vat_id' => 3, 'cost_price' => 70.00, 'unit_price' => 85.00],

                ['id' => 29, 'sku' => 'CON-0001', 'product_name' => 'Ketchup', 'category_id' => 11, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 55.00, 'unit_price' => 65.00],
                ['id' => 30, 'sku' => 'CON-0002', 'product_name' => 'Olive Oil', 'category_id' => 11, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 300.00, 'unit_price' => 350.00],

                ['id' => 31, 'sku' => 'HHS-0001', 'product_name' => 'Dish Soap', 'category_id' => 12, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 90.00, 'unit_price' => 110.00],
                ['id' => 32, 'sku' => 'HHS-0002', 'product_name' => 'Laundry Detergent', 'category_id' => 12, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 250.00, 'unit_price' => 280.00],

                ['id' => 33, 'sku' => 'PER-0001', 'product_name' => 'Shampoo', 'category_id' => 13, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 150.00, 'unit_price' => 170.00],
                ['id' => 34, 'sku' => 'PER-0002', 'product_name' => 'Body Wash', 'category_id' => 13, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 130.00, 'unit_price' => 150.00],

                ['id' => 35, 'sku' => 'HEA-0001', 'product_name' => 'Multivitamins', 'category_id' => 14, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 400.00, 'unit_price' => 450.00],
                ['id' => 36, 'sku' => 'HEA-0002', 'product_name' => 'Vitamin C', 'category_id' => 14, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 250.00, 'unit_price' => 280.00],

                ['id' => 37, 'sku' => 'BAB-0001', 'product_name' => 'Diapers', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 850.00, 'unit_price' => 950.00],
                ['id' => 38, 'sku' => 'BAB-0002', 'product_name' => 'Baby Wipes', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 39, 'sku' => 'BAB-0003', 'product_name' => 'Infant Formula', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 950.00, 'unit_price' => 1050.00],
                ['id' => 40, 'sku' => 'BAB-0004', 'product_name' => 'Baby Lotion', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_id' => 1, 'cost_price' => 180.00, 'unit_price' => 200.00],
            ]);
        
            // SEED INVENTORY LEVELS - atleast 40 match products above. Reorder level set to 20% of stock quantity
            DB::table('inventory_levels')->insert([
                ['product_id' => 1, 'quantity' => 100, 'reorder_level' => 20],
                ['product_id' => 2, 'quantity' => 150, 'reorder_level' => 30],
                ['product_id' => 3, 'quantity' => 80, 'reorder_level' => 16],
                ['product_id' => 4, 'quantity' => 60, 'reorder_level' => 12],
                ['product_id' => 5, 'quantity' => 40, 'reorder_level' => 8],
                ['product_id' => 6, 'quantity' => 70, 'reorder_level' => 14],
                ['product_id' => 7, 'quantity' => 50, 'reorder_level' => 10],
                ['product_id' => 8, 'quantity' => 30, 'reorder_level' => 6],
                ['product_id' => 9, 'quantity' => 40, 'reorder_level' => 8],
                ['product_id' => 10, 'quantity' => 90, 'reorder_level' => 18],
                ['product_id' => 11, 'quantity' => 120, 'reorder_level' => 24],
                ['product_id' => 12, 'quantity' => 110, 'reorder_level' => 22],
                ['product_id' => 13, 'quantity' => 75, 'reorder_level' => 15],
                ['product_id' => 14, 'quantity' => 85, 'reorder_level' => 17],
                ['product_id' => 15, 'quantity' => 65, 'reorder_level' => 13],
                ['product_id' => 16, 'quantity' => 55, 'reorder_level' => 11],
                ['product_id' => 17, 'quantity' => 45, 'reorder_level' => 9],
                ['product_id' => 18, 'quantity' => 95, 'reorder_level' => 19],
                ['product_id' => 19, 'quantity' => 100, 'reorder_level' => 20],
                ['product_id' => 20, 'quantity' => 70, 'reorder_level' => 14],
                ['product_id' => 21, 'quantity' => 70, 'reorder_level' => 14],
                ['product_id' => 22, 'quantity' => 60, 'reorder_level' => 12],
                ['product_id' => 23, 'quantity' => 80, 'reorder_level' => 16],
                ['product_id' => 24, 'quantity' => 50, 'reorder_level' => 10],
                ['product_id' => 25, 'quantity' => 25, 'reorder_level' => 5],
                ['product_id' => 26, 'quantity' => 40, 'reorder_level' => 8],
                ['product_id' => 27, 'quantity' => 90, 'reorder_level' => 18],
                ['product_id' => 28, 'quantity' => 85, 'reorder_level' => 17],
                ['product_id' => 29, 'quantity' => 55, 'reorder_level' => 11],
                ['product_id' => 30, 'quantity' => 65, 'reorder_level' => 13],
                ['product_id' => 31, 'quantity' => 60, 'reorder_level' => 12],
                ['product_id' => 32, 'quantity' => 70, 'reorder_level' => 14],
                ['product_id' => 33, 'quantity' => 90, 'reorder_level' => 18],
                ['product_id' => 34, 'quantity' => 100, 'reorder_level' => 20],
                ['product_id' => 35, 'quantity' => 80, 'reorder_level' => 16],
                ['product_id' => 36, 'quantity' => 95, 'reorder_level' => 19],
                ['product_id' => 37, 'quantity' => 90, 'reorder_level' => 18],
                ['product_id' => 38, 'quantity' => 100, 'reorder_level' => 20],
                ['product_id' => 39, 'quantity' => 70, 'reorder_level' => 14],
                ['product_id' => 40, 'quantity' => 80, 'reorder_level' => 16],  
            ]);
    
            // SEED STOCK MOVEMENTS - atleast 40 match INVENTORY LEVELS above. Quantity should match inventory levels above
            // add expiry date for perishable items like dairy, meat, seafood, frozen foods, fruits & vegetables
            DB::table('stock_movements')->insert([
                ['product_id' => 1, 'quantity' => 100, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH001', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 2, 'quantity' => 150, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH002', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 3, 'quantity' => 80, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH003', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 4, 'quantity' => 60, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH004', 'expiry_date' => now()->addDays(10), 'remarks' => 'Initial stock entry'],
                ['product_id' => 5, 'quantity' => 40, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH005', 'expiry_date' => now()->addDays(15), 'remarks' => 'Initial stock entry'],
                ['product_id' => 6, 'quantity' => 70, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH006', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 7, 'quantity' => 50, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => null, 'expiry_date' => now()->addDays(7), 'remarks' => 'Initial stock entry'],
                ['product_id' => 8, 'quantity' => 30, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => null, 'expiry_date' => now()->addDays(5), 'remarks' => 'Initial stock entry'],
                ['product_id' => 9, 'quantity' => 40, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH009', 'expiry_date' => now()->addDays(30), 'remarks' => 'Initial stock entry'],
                ['product_id' => 10, 'quantity' => 90, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH010', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 11, 'quantity' => 120, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH011', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 12, 'quantity' => 110, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH012', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 13, 'quantity' => 75, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH013', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 14, 'quantity' => 85, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH014', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 15, 'quantity' => 65, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH015', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 16, 'quantity' => 55, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH016', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 17, 'quantity' => 45, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH017', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 18, 'quantity' => 95, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH018', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 19, 'quantity' => 100, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH019', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 20, 'quantity' => 70, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH020', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 21, 'quantity' => 70, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH021', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 22, 'quantity' => 60, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH022', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 23, 'quantity' => 80, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH023', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 24, 'quantity' => 50, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH024', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 25, 'quantity' => 25, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH025', 'expiry_date' => now()->addDays(7), 'remarks' => 'Initial stock entry'],
                ['product_id' => 26, 'quantity' => 40, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH026', 'expiry_date' => now()->addDays(5), 'remarks' => 'Initial stock entry'],
                ['product_id' => 27, 'quantity' => 90, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => null, 'expiry_date' => now()->addDays(10), 'remarks' => 'Initial stock entry'],
                ['product_id' => 28, 'quantity' => 85, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => null, 'expiry_date' => now()->addDays(15), 'remarks' => 'Initial stock entry'],
                ['product_id' => 29, 'quantity' => 55, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH029', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 30, 'quantity' => 65, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH030', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 31, 'quantity' => 60, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH031', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 32, 'quantity' => 70, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH032', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 33, 'quantity' => 90, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH033', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 34, 'quantity' => 100, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH034', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 35, 'quantity' => 80, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH035', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 36, 'quantity' => 95, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH036', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 37, 'quantity' => 90, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => null, 'expiry_date' => now()->addDays(7), 'remarks' => 'Initial stock entry'],
                ['product_id' => 38, 'quantity' => 100, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => null, 'expiry_date' => now()->addDays(5), 'remarks' => 'Initial stock entry'],
                ['product_id' => 39, 'quantity' => 70, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH039', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],
                ['product_id' => 40, 'quantity' => 80, 'movement_type' => 'stock_in', 'reference' => 'Initial Stock', 'batch_number' => 'BATCH040', 'expiry_date' => null, 'remarks' => 'Initial stock entry'],  
            ]);
        }

    }
};
