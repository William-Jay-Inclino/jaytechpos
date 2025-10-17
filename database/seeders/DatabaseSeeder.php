<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // SEED USER - Jay
        $user = User::create([
            'name' => 'Jay',
            'email' => 'wjay.inclino@gmail.com',
            'password' => bcrypt('IamJay123'),
            'email_verified_at' => now(),
        ]);

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
            ['user_id' => $user->id, 'name' => 'Alice Johnson', 'mobile_number' => '09171234567'],
            ['user_id' => $user->id, 'name' => 'Bob Smith', 'mobile_number' => '09179876543'],
            ['user_id' => $user->id, 'name' => 'Charlie Brown', 'mobile_number' => '09281234567'],
            ['user_id' => $user->id, 'name' => 'Diana Prince', 'mobile_number' => '09381234567'],
            ['user_id' => $user->id, 'name' => 'Ethan Hunt', 'mobile_number' => '09481234567'],
        ]);

        // SEED VAT RATES - atleast 2 rates
        DB::table('vat_rates')->insert([
            ['rate_name' => 'VAT 12%', 'rate_percentage' => 12.00],
            ['rate_name' => 'VAT 10%', 'rate_percentage' => 10.00],
        ]);

        // SEED CATEGORIES - context mini grocery store atleast 15 categories. Group by type of products
        DB::table('product_categories')->insert([
            ['user_id' => $user->id, 'name' => 'Beverages', 'description' => 'Drinks and refreshments'],
            ['user_id' => $user->id, 'name' => 'Snacks', 'description' => 'Chips, cookies, and other snacks'],
            ['user_id' => $user->id, 'name' => 'Dairy Products', 'description' => 'Milk, cheese, yogurt, etc.'],
            ['user_id' => $user->id, 'name' => 'Bakery', 'description' => 'Bread, pastries, and baked goods'],
            ['user_id' => $user->id, 'name' => 'Fruits & Vegetables', 'description' => 'Fresh produce'],
            ['user_id' => $user->id, 'name' => 'Meat & Poultry', 'description' => 'Fresh and processed meats'],
            ['user_id' => $user->id, 'name' => 'Seafood', 'description' => 'Fresh and frozen fish and seafood'],
            ['user_id' => $user->id, 'name' => 'Frozen Foods', 'description' => 'Frozen meals and ingredients'],
            ['user_id' => $user->id, 'name' => 'Canned Goods', 'description' => 'Canned vegetables, soups, etc.'],
            ['user_id' => $user->id, 'name' => 'Dry Goods', 'description' => 'Rice, pasta, beans, etc.'],
            ['user_id' => $user->id, 'name' => 'Condiments & Sauces', 'description' => 'Ketchup, mustard, sauces, etc.'],
            ['user_id' => $user->id, 'name' => 'Household Supplies', 'description' => 'Cleaning products and supplies'],
            ['user_id' => $user->id, 'name' => 'Personal Care', 'description' => 'Toiletries and personal care items'],
            ['user_id' => $user->id, 'name' => 'Health & Wellness', 'description' => 'Vitamins and supplements'],
            ['user_id' => $user->id, 'name' => 'Baby Products', 'description' => 'Diapers, baby food, etc.'],
        ]);

        if (App::environment('local')) {

            // SEED SUPPLIERS - atleast 10 suppliers
            DB::table('suppliers')->insert([
                ['user_id' => $user->id, 'supplier_name' => 'Fresh Farms', 'contact_name' => 'John Doe', 'address' => '123 Farm Lane', 'phone' => '555-1234', 'email' => 'contact@freshfarms.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Grocery Wholesale', 'contact_name' => 'Jane Smith', 'address' => '456 Market St', 'phone' => '555-5678', 'email' => 'contact@grocerywholesale.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Local Produce Co.', 'contact_name' => 'Alice Johnson', 'address' => '789 Orchard Ave', 'phone' => '555-8765', 'email' => 'contact@localproduce.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Dairy Delights', 'contact_name' => 'Bob Brown', 'address' => '321 Dairy Rd', 'phone' => '555-4321', 'email' => 'contact@dairydelights.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Meat Masters', 'contact_name' => 'Charlie White', 'address' => '654 Butcher Blvd', 'phone' => '555-2468', 'email' => 'contact@meatmasters.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Seafood Shack', 'contact_name' => 'Diana Green', 'address' => '987 Ocean Dr', 'phone' => '555-1357', 'email' => 'contact@seafoodshack.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Bakery Bliss', 'contact_name' => 'Eve Black', 'address' => '159 Bread St', 'phone' => '555-8642', 'email' => 'contact@bakerybliss.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Snack Attack', 'contact_name' => 'Frank Blue', 'address' => '753 Snack Ave', 'phone' => '555-7531', 'email' => 'contact@snackattack.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Beverage Hub', 'contact_name' => 'Grace Red', 'address' => '951 Drink Blvd', 'phone' => '555-1597', 'email' => 'contact@beveragehub.com'],
                ['user_id' => $user->id, 'supplier_name' => 'Frozen Foods Inc.', 'contact_name' => 'Hank Yellow', 'address' => '357 Ice Cream Rd', 'phone' => '555-3579', 'email' => 'contact@frozenfoods.com'],
            ]);

            // SEED PRODUCTS - atleast 40 products
            DB::table('products')->insert([
                ['id' => 1, 'user_id' => $user->id, 'sku' => 'BEV-0001', 'product_name' => 'Orange Juice', 'category_id' => 1, 'supplier_id' => 9, 'unit_id' => 4, 'vat_type' => 'non_vat', 'cost_price' => 85.00, 'unit_price' => 95.00],
                ['id' => 2, 'user_id' => $user->id, 'sku' => 'BEV-0002', 'product_name' => 'Milk', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 4, 'vat_type' => 'non_vat', 'cost_price' => 60.00, 'unit_price' => 70.00],
                ['id' => 3, 'user_id' => $user->id, 'sku' => 'BEV-0003', 'product_name' => 'Cola Drink', 'category_id' => 1, 'supplier_id' => 9, 'unit_id' => 4, 'vat_type' => 'non_vat', 'cost_price' => 25.00, 'unit_price' => 30.00],
                ['id' => 4, 'user_id' => $user->id, 'sku' => 'BEV-0004', 'product_name' => 'Energy Drink', 'category_id' => 1, 'supplier_id' => 9, 'unit_id' => 4, 'vat_type' => 'non_vat', 'cost_price' => 45.00, 'unit_price' => 55.00],

                ['id' => 5, 'user_id' => $user->id, 'sku' => 'SNK-0001', 'product_name' => 'Chocolate Chip Cookies', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 90.00, 'unit_price' => 110.00],
                ['id' => 6, 'user_id' => $user->id, 'sku' => 'SNK-0002', 'product_name' => 'Potato Chips', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 7, 'user_id' => $user->id, 'sku' => 'SNK-0003', 'product_name' => 'Granola Bars', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 8, 'user_id' => $user->id, 'sku' => 'SNK-0004', 'product_name' => 'Pretzels', 'category_id' => 2, 'supplier_id' => 8, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 40.00, 'unit_price' => 50.00],

                ['id' => 9, 'user_id' => $user->id, 'sku' => 'DAI-0001', 'product_name' => 'Cheddar Cheese', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 2, 'vat_type' => 'non_vat', 'cost_price' => 180.00, 'unit_price' => 200.00],
                ['id' => 10, 'user_id' => $user->id, 'sku' => 'DAI-0002', 'product_name' => 'Yogurt', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 11, 'user_id' => $user->id, 'sku' => 'DAI-0003', 'product_name' => 'Butter', 'category_id' => 3, 'supplier_id' => 4, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 150.00, 'unit_price' => 165.00],

                ['id' => 12, 'user_id' => $user->id, 'sku' => 'BAK-0001', 'product_name' => 'Whole Wheat Bread', 'category_id' => 4, 'supplier_id' => 7, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 45.00, 'unit_price' => 55.00],
                ['id' => 13, 'user_id' => $user->id, 'sku' => 'BAK-0002', 'product_name' => 'Bagels', 'category_id' => 4, 'supplier_id' => 7, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 50.00, 'unit_price' => 65.00],

                ['id' => 14, 'user_id' => $user->id, 'sku' => 'FRU-0001', 'product_name' => 'Apple', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_type' => 'vat_zero_rated', 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 15, 'user_id' => $user->id, 'sku' => 'FRU-0002', 'product_name' => 'Banana', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_type' => 'vat_zero_rated', 'cost_price' => 60.00, 'unit_price' => 75.00],
                ['id' => 16, 'user_id' => $user->id, 'sku' => 'FRU-0003', 'product_name' => 'Grapes', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_type' => 'vat_zero_rated', 'cost_price' => 180.00, 'unit_price' => 200.00],
                ['id' => 17, 'user_id' => $user->id, 'sku' => 'FRU-0004', 'product_name' => 'Strawberries', 'category_id' => 5, 'supplier_id' => 3, 'unit_id' => 2, 'vat_type' => 'vat_zero_rated', 'cost_price' => 200.00, 'unit_price' => 230.00],

                ['id' => 18, 'user_id' => $user->id, 'sku' => 'MEP-0001', 'product_name' => 'Chicken Breast', 'category_id' => 6, 'supplier_id' => 5, 'unit_id' => 2, 'vat_type' => 'non_vat', 'cost_price' => 180.00, 'unit_price' => 200.00],
                ['id' => 19, 'user_id' => $user->id, 'sku' => 'MEP-0002', 'product_name' => 'Turkey Slices', 'category_id' => 6, 'supplier_id' => 5, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 150.00, 'unit_price' => 170.00],

                ['id' => 20, 'user_id' => $user->id, 'sku' => 'SEA-0001', 'product_name' => 'Salmon Fillet', 'category_id' => 7, 'supplier_id' => 6, 'unit_id' => 2, 'vat_type' => 'non_vat', 'cost_price' => 400.00, 'unit_price' => 450.00],
                ['id' => 21, 'user_id' => $user->id, 'sku' => 'SEA-0002', 'product_name' => 'Shrimp', 'category_id' => 7, 'supplier_id' => 6, 'unit_id' => 2, 'vat_type' => 'non_vat', 'cost_price' => 350.00, 'unit_price' => 400.00],

                ['id' => 22, 'user_id' => $user->id, 'sku' => 'FF-0001', 'product_name' => 'Frozen Pizza', 'category_id' => 8, 'supplier_id' => 10, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 220.00, 'unit_price' => 250.00],
                ['id' => 23, 'user_id' => $user->id, 'sku' => 'FF-0002', 'product_name' => 'Ice Cream', 'category_id' => 8, 'supplier_id' => 10, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 150.00, 'unit_price' => 180.00],

                ['id' => 24, 'user_id' => $user->id, 'sku' => 'CAG-0001', 'product_name' => 'Canned Beans', 'category_id' => 9, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 25, 'user_id' => $user->id, 'sku' => 'CAG-0002', 'product_name' => 'Tomato Soup', 'category_id' => 9, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 40.00, 'unit_price' => 50.00],
                ['id' => 26, 'user_id' => $user->id, 'sku' => 'CAG-0003', 'product_name' => 'Black Beans', 'category_id' => 9, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 38.00, 'unit_price' => 48.00],

                ['id' => 27, 'user_id' => $user->id, 'sku' => 'DRG-0001', 'product_name' => 'Rice', 'category_id' => 10, 'supplier_id' => 2, 'unit_id' => 2, 'vat_type' => 'vat_zero_rated', 'cost_price' => 1800.00, 'unit_price' => 2000.00],
                ['id' => 28, 'user_id' => $user->id, 'sku' => 'DRG-0002', 'product_name' => 'Pasta', 'category_id' => 10, 'supplier_id' => 2, 'unit_id' => 2, 'vat_type' => 'vat_zero_rated', 'cost_price' => 70.00, 'unit_price' => 85.00],

                ['id' => 29, 'user_id' => $user->id, 'sku' => 'CON-0001', 'product_name' => 'Ketchup', 'category_id' => 11, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 55.00, 'unit_price' => 65.00],
                ['id' => 30, 'user_id' => $user->id, 'sku' => 'CON-0002', 'product_name' => 'Olive Oil', 'category_id' => 11, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 300.00, 'unit_price' => 350.00],

                ['id' => 31, 'user_id' => $user->id, 'sku' => 'HHS-0001', 'product_name' => 'Dish Soap', 'category_id' => 12, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 90.00, 'unit_price' => 110.00],
                ['id' => 32, 'user_id' => $user->id, 'sku' => 'HHS-0002', 'product_name' => 'Laundry Detergent', 'category_id' => 12, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 250.00, 'unit_price' => 280.00],

                ['id' => 33, 'user_id' => $user->id, 'sku' => 'PER-0001', 'product_name' => 'Shampoo', 'category_id' => 13, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 150.00, 'unit_price' => 170.00],
                ['id' => 34, 'user_id' => $user->id, 'sku' => 'PER-0002', 'product_name' => 'Body Wash', 'category_id' => 13, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 130.00, 'unit_price' => 150.00],

                ['id' => 35, 'user_id' => $user->id, 'sku' => 'HEA-0001', 'product_name' => 'Multivitamins', 'category_id' => 14, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 400.00, 'unit_price' => 450.00],
                ['id' => 36, 'user_id' => $user->id, 'sku' => 'HEA-0002', 'product_name' => 'Vitamin C', 'category_id' => 14, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'non_vat', 'cost_price' => 250.00, 'unit_price' => 280.00],

                ['id' => 37, 'user_id' => $user->id, 'sku' => 'BAB-0001', 'product_name' => 'Diapers', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'vat_exempt', 'cost_price' => 850.00, 'unit_price' => 950.00],
                ['id' => 38, 'user_id' => $user->id, 'sku' => 'BAB-0002', 'product_name' => 'Baby Wipes', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'vat_exempt', 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 39, 'user_id' => $user->id, 'sku' => 'BAB-0003', 'product_name' => 'Infant Formula', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'vat_exempt', 'cost_price' => 950.00, 'unit_price' => 1050.00],
                ['id' => 40, 'user_id' => $user->id, 'sku' => 'BAB-0004', 'product_name' => 'Baby Lotion', 'category_id' => 15, 'supplier_id' => 2, 'unit_id' => 1, 'vat_type' => 'vat_exempt', 'cost_price' => 180.00, 'unit_price' => 200.00],
            ]);

        }

    }
}
