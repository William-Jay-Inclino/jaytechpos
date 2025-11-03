<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');

            return;
        }

        // Create product categories for each user
        foreach ($users as $user) {
            $this->createProductCategoriesForUser($user);
            $this->createExpenseCategoriesForUser($user);
            $this->command->info("Created default categories for user: {$user->name}");
        }
    }

    private function createProductCategoriesForUser(User $user): void
    {
        $categories = [
            // Food & Beverages
            ['name' => 'Beverages', 'description' => 'Drinks and refreshments'],
            ['name' => 'Snacks & Confectionery', 'description' => 'Chips, cookies, candies, and other snacks'],
            ['name' => 'Dairy Products', 'description' => 'Milk, cheese, yogurt, etc.'],
            ['name' => 'Bakery & Bread', 'description' => 'Bread, pastries, and baked goods'],
            ['name' => 'Fruits & Vegetables', 'description' => 'Fresh produce'],
            ['name' => 'Rice & Grains', 'description' => 'Rice, corn, quinoa, and other grains'],
            ['name' => 'Canned & Preserved Foods', 'description' => 'Canned goods, jarred items, preserved foods'],
            ['name' => 'Condiments & Seasonings', 'description' => 'Sauces, spices, salt, vinegar, etc.'],
            ['name' => 'Frozen Foods', 'description' => 'Frozen vegetables, meat, ice cream, etc.'],
            ['name' => 'Meat & Seafood', 'description' => 'Fresh and processed meat, fish, and seafood'],

            // Personal Care & Health
            ['name' => 'Personal Care', 'description' => 'Toiletries, cosmetics, hygiene products'],
            ['name' => 'Health & Medicine', 'description' => 'Over-the-counter medicines, vitamins, first aid'],
            ['name' => 'Baby Care', 'description' => 'Diapers, baby food, baby care products'],

            // Household Items
            ['name' => 'Cleaning Supplies', 'description' => 'Detergents, soaps, cleaning tools'],
            ['name' => 'Kitchen & Dining', 'description' => 'Cookware, utensils, plates, cups'],
            ['name' => 'Home & Garden', 'description' => 'Home decor, gardening tools, plants'],
            ['name' => 'Hardware & Tools', 'description' => 'Basic tools, nails, screws, electrical items'],

            // Clothing & Accessories
            ['name' => 'Clothing & Apparel', 'description' => 'Shirts, pants, dresses, undergarments'],
            ['name' => 'Shoes & Footwear', 'description' => 'Shoes, slippers, sandals'],
            ['name' => 'Bags & Accessories', 'description' => 'Bags, wallets, jewelry, watches'],

            // Electronics & Gadgets
            ['name' => 'Electronics', 'description' => 'Phones, chargers, batteries, small appliances'],
            ['name' => 'School & Office Supplies', 'description' => 'Notebooks, pens, paper, school materials'],

            // Miscellaneous
            ['name' => 'Toys & Games', 'description' => 'Children toys, games, entertainment items'],
            ['name' => 'Pet Supplies', 'description' => 'Pet food, toys, and care products'],
            ['name' => 'Automotive', 'description' => 'Car accessories, motor oil, car care products'],
            ['name' => 'Sports & Recreation', 'description' => 'Sports equipment, outdoor gear'],
            ['name' => 'Books & Media', 'description' => 'Books, magazines, CDs, DVDs'],
            ['name' => 'Gifts & Novelties', 'description' => 'Gift items, souvenirs, novelty products'],
        ];

        $categories = collect($categories)
            ->map(fn ($category) => array_merge($category, [
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]))
            ->toArray();

        \DB::table('product_categories')->insert($categories);
    }

    private function createExpenseCategoriesForUser(User $user): void
    {
        $categories = [
            ['name' => 'Supplies'],
            ['name' => 'Utilities'],
            ['name' => 'Equipment & Maintenance'],
            ['name' => 'Transportation'],
            ['name' => 'Rent'],
            ['name' => 'Government Fees & Permits'],
            ['name' => 'Staff Wages'],
            ['name' => 'Insurance'],
            ['name' => 'Miscellaneous'],
        ];

        $categories = collect($categories)
            ->map(fn ($category) => array_merge($category, [
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]))
            ->toArray();

        \DB::table('expense_categories')->insert($categories);
    }
}
