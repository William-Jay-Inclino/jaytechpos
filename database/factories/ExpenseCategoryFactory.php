<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = [
            '#3B82F6', // Blue
            '#EF4444', // Red
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#8B5CF6', // Purple
            '#F97316', // Orange
            '#06B6D4', // Cyan
            '#84CC16', // Lime
            '#EC4899', // Pink
            '#6B7280', // Gray
        ];

        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->unique()->randomElement(['Office Expenses', 'Rent & Utilities', 'Marketing', 'Equipment', 'Travel', 'Professional Services', 'Insurance', 'Software & Licenses', 'Maintenance', 'Miscellaneous']),
            'color' => fake()->randomElement($colors),
        ];
    }
}
