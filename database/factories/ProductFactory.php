<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
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
            'category_id' => \App\Models\ProductCategory::factory(),
            'unit_id' => \App\Models\Unit::factory(),
            'product_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'cost_price' => fake()->randomFloat(2, 5, 500),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
