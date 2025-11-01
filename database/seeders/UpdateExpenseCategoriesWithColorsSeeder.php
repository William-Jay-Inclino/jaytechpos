<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class UpdateExpenseCategoriesWithColorsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
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

        // Get all categories (including those with default colors)
        $categories = ExpenseCategory::all();

        foreach ($categories as $index => $category) {
            $category->update([
                'color' => $colors[$index % count($colors)],
            ]);
        }

        $this->command->info('Updated '.$categories->count().' expense categories with colors.');
    }
}
