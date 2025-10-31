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

        // SEED UNITS - comprehensive units for Philippine retail stores
        DB::table('units')->insert([
            // Basic counting units
            ['unit_name' => 'Piece', 'abbreviation' => 'pc/pcs'],
            ['unit_name' => 'Pair', 'abbreviation' => 'pr'],
            ['unit_name' => 'Set', 'abbreviation' => 'set'],
            ['unit_name' => 'Dozen', 'abbreviation' => 'dz'],
            
            // Weight units
            ['unit_name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['unit_name' => 'Gram', 'abbreviation' => 'g'],
            ['unit_name' => 'Pound', 'abbreviation' => 'lb'],
            ['unit_name' => 'Ounce', 'abbreviation' => 'oz'],
            
            // Volume units
            ['unit_name' => 'Liter', 'abbreviation' => 'L'],
            ['unit_name' => 'Milliliter', 'abbreviation' => 'mL'],
            ['unit_name' => 'Gallon', 'abbreviation' => 'gal'],
            
            // Packaging units
            ['unit_name' => 'Pack', 'abbreviation' => 'pk'],
            ['unit_name' => 'Box', 'abbreviation' => 'bx'],
            ['unit_name' => 'Case', 'abbreviation' => 'cs'],
            ['unit_name' => 'Bundle', 'abbreviation' => 'bdl'],
            ['unit_name' => 'Bag', 'abbreviation' => 'bg'],
            ['unit_name' => 'Bottle', 'abbreviation' => 'btl'],
            ['unit_name' => 'Can', 'abbreviation' => 'cn'],
            ['unit_name' => 'Jar', 'abbreviation' => 'jr'],
            ['unit_name' => 'Tube', 'abbreviation' => 'tb'],
            ['unit_name' => 'Pouch', 'abbreviation' => 'pch'],
            
            // Length/Area units
            ['unit_name' => 'Meter', 'abbreviation' => 'm'],
            ['unit_name' => 'Centimeter', 'abbreviation' => 'cm'],
            ['unit_name' => 'Inch', 'abbreviation' => 'in'],
            ['unit_name' => 'Foot', 'abbreviation' => 'ft'],
            ['unit_name' => 'Yard', 'abbreviation' => 'yd'],
            ['unit_name' => 'Roll', 'abbreviation' => 'rl'],
            ['unit_name' => 'Sheet', 'abbreviation' => 'sht'],
            
            // Special units common in Philippines
            ['unit_name' => 'Sack', 'abbreviation' => 'sk'],
            ['unit_name' => 'Kilo', 'abbreviation' => 'k'],
            ['unit_name' => 'Tray', 'abbreviation' => 'try'],
            ['unit_name' => 'Sachet', 'abbreviation' => 'sct'],
        ]);

        // SEED CUSTOMER - atleast 5 customers
        DB::table('customers')->insert([
            ['user_id' => $user->id, 'name' => 'Alice Johnson', 'mobile_number' => '09171234567', 'interest_rate' => 3.00],
            ['user_id' => $user->id, 'name' => 'Bob Smith', 'mobile_number' => '09179876543', 'interest_rate' => 3.00],
            ['user_id' => $user->id, 'name' => 'Charlie Brown', 'mobile_number' => '09281234567', 'interest_rate' => 3.00],
            ['user_id' => $user->id, 'name' => 'Diana Prince', 'mobile_number' => '09381234567', 'interest_rate' => 3.00],
            ['user_id' => $user->id, 'name' => 'Ethan Hunt', 'mobile_number' => '09481234567', 'interest_rate' => 3.00],
        ]);

        // SEED CATEGORIES - using UserSetupService to avoid duplication
        $userSetupService = new \App\Services\UserSetupService();
        $userSetupService->createDefaultCategories($user);

        if (App::environment('local')) {

            // SEED PRODUCTS - atleast 8 products
            DB::table('products')->insert([
                // Beverages (category_id = 1)
                ['id' => 1, 'user_id' => $user->id, 'product_name' => 'Orange Juice', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 95.00], // Bottle
                ['id' => 3, 'user_id' => $user->id, 'product_name' => 'Cola Drink', 'category_id' => 1, 'unit_id' => 17,'cost_price' => 25.00, 'unit_price' => 30.00], // Bottle
                ['id' => 4, 'user_id' => $user->id, 'product_name' => 'Energy Drink', 'category_id' => 1, 'unit_id' => 18, 'cost_price' => 45.00, 'unit_price' => 55.00], // Can
                
                // Dairy Products (category_id = 3)  
                ['id' => 2, 'user_id' => $user->id, 'product_name' => 'Milk', 'category_id' => 3, 'unit_id' => 9, 'cost_price' => 60.00, 'unit_price' => 70.00], // Liter

                // Snacks & Confectionery (category_id = 2)
                ['id' => 5, 'user_id' => $user->id, 'product_name' => 'Chocolate Chip Cookies', 'category_id' => 2, 'unit_id' => 12, 'cost_price' => 90.00, 'unit_price' => 110.00], // Pack
                ['id' => 6, 'user_id' => $user->id, 'product_name' => 'Potato Chips', 'category_id' => 2, 'unit_id' => 12, 'cost_price' => 35.00, 'unit_price' => 45.00], // Pack
                ['id' => 7, 'user_id' => $user->id, 'product_name' => 'Granola Bars', 'category_id' => 2, 'unit_id' => 13, 'cost_price' => 120.00, 'unit_price' => 140.00], // Box
                ['id' => 8, 'user_id' => $user->id, 'product_name' => 'Pretzels', 'category_id' => 2, 'unit_id' => 16, 'cost_price' => 40.00, 'unit_price' => 50.00], // Bag

            ]);

        }

    }
}
