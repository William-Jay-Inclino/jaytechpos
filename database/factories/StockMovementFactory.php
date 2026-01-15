<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => $this->faker->randomElement(StockMovementType::cases()),
            'quantity' => $this->faker->randomFloat(2, 1, 1000),
            'reference' => $this->faker->optional()->word(),
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }
}
