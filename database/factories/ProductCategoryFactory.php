<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Electronics', 'Food & Beverage', 'Clothing', 'Home & Garden', 'Books', 'Sports', 'Health & Beauty', 'Automotive', 'Toys & Games', 'Office Supplies']),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'is_default' => false,
        ];
    }
}
