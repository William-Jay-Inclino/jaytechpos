<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call individual seeders in the correct order
        $this->call([
            // Basic setup
            UserSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            UpdateExpenseCategoriesWithColorsSeeder::class,

            // Business data
            CustomerSeeder::class,
            ProductSeeder::class,

            // Transactions (in chronological order)
            SaleSeeder::class,
            MonthlyInterestSeeder::class,

            // Rebuild customer transactions in proper chronological order
            CustomerTransactionRebuilderSeeder::class,

            ExpenseSeeder::class,
        ]);
    }
}
