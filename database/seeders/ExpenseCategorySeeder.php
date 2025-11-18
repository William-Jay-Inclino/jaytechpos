<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', '!=', UserRole::Admin->value)->get();

        $categories = [
            'Rent',
            'Utilities',
            'Staff Wages',
            'Supplies',
            'Transportation',
            'Equipment & Maintenance',
            'Government Fees & Permits',
            'Insurance',
            'Marketing & Advertising',
            'Miscellaneous',
        ];

        foreach ($users as $user) {
            foreach ($categories as $categoryName) {
                ExpenseCategory::create([
                    'user_id' => $user->id,
                    'name' => $categoryName,
                    'color' => '#6B7280', // Default gray, will be updated by UpdateExpenseCategoriesWithColorsSeeder
                ]);
            }

            $this->command->info("Created expense categories for user: {$user->name}");
        }
    }
}
