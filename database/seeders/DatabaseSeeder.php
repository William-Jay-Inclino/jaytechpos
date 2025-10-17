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

        // SEED CATEGORIES - context mini grocery store atleast 5 categories. Group by type of products
        DB::table('product_categories')->insert([
            ['user_id' => $user->id, 'name' => 'Beverages', 'description' => 'Drinks and refreshments'],
            ['user_id' => $user->id, 'name' => 'Snacks', 'description' => 'Chips, cookies, and other snacks'],
            ['user_id' => $user->id, 'name' => 'Dairy Products', 'description' => 'Milk, cheese, yogurt, etc.'],
            ['user_id' => $user->id, 'name' => 'Bakery', 'description' => 'Bread, pastries, and baked goods'],
            ['user_id' => $user->id, 'name' => 'Fruits & Vegetables', 'description' => 'Fresh produce'],
        ]);

        if (App::environment('local')) {

            // SEED PRODUCTS - atleast 8 products
            DB::table('products')->insert([
                ['id' => 1, 'user_id' => $user->id, 'product_name' => 'Orange Juice', 'category_id' => 1, 'unit_id' => 4, 'cost_price' => 85.00, 'unit_price' => 95.00],
                ['id' => 2, 'user_id' => $user->id, 'product_name' => 'Milk', 'category_id' => 3, 'unit_id' => 4, 'cost_price' => 60.00, 'unit_price' => 70.00],
                ['id' => 3, 'user_id' => $user->id, 'product_name' => 'Cola Drink', 'category_id' => 1, 'unit_id' => 4,'cost_price' => 25.00, 'unit_price' => 30.00],
                ['id' => 4, 'user_id' => $user->id, 'product_name' => 'Energy Drink', 'category_id' => 1, 'unit_id' => 4, 'cost_price' => 45.00, 'unit_price' => 55.00],

                ['id' => 5, 'user_id' => $user->id, 'product_name' => 'Chocolate Chip Cookies', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 90.00, 'unit_price' => 110.00],
                ['id' => 6, 'user_id' => $user->id, 'product_name' => 'Potato Chips', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 35.00, 'unit_price' => 45.00],
                ['id' => 7, 'user_id' => $user->id, 'product_name' => 'Granola Bars', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 120.00, 'unit_price' => 140.00],
                ['id' => 8, 'user_id' => $user->id, 'product_name' => 'Pretzels', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 40.00, 'unit_price' => 50.00],

            ]);

        }

    }
}
