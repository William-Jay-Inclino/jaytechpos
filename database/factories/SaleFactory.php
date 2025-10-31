<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
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
            'customer_id' => \App\Models\Customer::factory(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'paid_amount' => $this->faker->randomFloat(2, 10, 1000),
            'previous_balance' => $this->faker->randomFloat(2, 0, 500),
            'new_balance' => $this->faker->randomFloat(2, 0, 500),
            'invoice_number' => $this->faker->unique()->numerify('INV-########'),
            'payment_type' => $this->faker->randomElement(['cash', 'utang']),
            'transaction_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
