<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtangPayment>
 */
class UtangPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousBalance = $this->faker->randomFloat(2, 100, 2000);
        $paymentAmount = $this->faker->randomFloat(2, 10, 1000);

        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'payment_amount' => $paymentAmount,
            'previous_balance' => $previousBalance,
            'new_balance' => $previousBalance - $paymentAmount,
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
