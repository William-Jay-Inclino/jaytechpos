<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerTransaction>
 */
class CustomerTransactionFactory extends Factory
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
            'transaction_type' => 'sale',
            'transaction_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'previous_balance' => 0.00,
            'new_balance' => $this->faker->randomFloat(2, 50, 2000),
            'transaction_amount' => $this->faker->randomFloat(2, 50, 2000),
            'transaction_desc' => $this->faker->sentence(),
            'reference_id' => null,
        ];
    }

    /**
     * Create a sale transaction state.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'sale',
        ]);
    }

    /**
     * Create a payment transaction state.
     */
    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'utang_payment',
        ]);
    }

    /**
     * Create a monthly interest transaction state.
     */
    public function monthlyInterest(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'monthly_interest',
        ]);
    }
}
