<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\ExpenseCategory::factory(),
            'name' => fake()->randomElement(['Office Supplies', 'Rent Payment', 'Utilities', 'Equipment Purchase', 'Marketing', 'Travel Expenses', 'Software License', 'Maintenance', 'Insurance', 'Professional Services']),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'expense_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
