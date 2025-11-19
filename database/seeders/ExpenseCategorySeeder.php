<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', '!=', UserRole::Admin->value)->get();

        $categories = ExpenseCategory::defaultCategories();

        foreach ($users as $user) {
            foreach ($categories as $category) {
                ExpenseCategory::create([
                    'user_id' => $user->id,
                    'name' => $category['name'],
                    'color' => $category['color'],
                ]);
            }

            $this->command->info("Created expense categories for user: {$user->name}");
        }
    }
}
