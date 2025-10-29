<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtangTracking>
 */
class UtangTrackingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'beginning_balance' => fake()->randomFloat(2, 0, 10000),
            'computation_date' => now()->startOfMonth(),
            'interest_rate' => 3.00,
        ];
    }

    /**
     * Indicate the tracking is for current month.
     */
    public function currentMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'computation_date' => now()->startOfMonth(),
        ]);
    }

    /**
     * Indicate the tracking is for previous month.
     */
    public function previousMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'computation_date' => now()->subMonth()->startOfMonth(),
        ]);
    }
}
