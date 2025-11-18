<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Enums\UserRole;

class MonthlyInterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', '!=', UserRole::Admin->value)->get();

        foreach ($users as $user) {
            $this->createMonthlyInterestForUser($user);
        }
    }

    private function createMonthlyInterestForUser(User $user): void
    {
        // Get customers with utang
        $customersWithUtang = Customer::where('user_id', $user->id)
            ->where('has_utang', true)
            ->get();

        if ($customersWithUtang->isEmpty()) {
            $this->command->warn("No customers with utang found for user: {$user->name}");

            return;
        }

        $totalInterestRecords = 0;

        // Process each month from February 2025 to November 2025 (interest starts from 2nd month)
        for ($month = 2; $month <= 11; $month++) {
            $computationDate = Carbon::create(2025, $month, 1, 9, 0, 0); // 9 AM on 1st of month

            foreach ($customersWithUtang as $customer) {
                if ($this->shouldProcessInterest($customer, $computationDate)) {
                    $this->processMonthlyInterest($user, $customer, $computationDate);
                    $totalInterestRecords++;
                }
            }
        }

        $this->command->info("Created {$totalInterestRecords} monthly interest records for user: {$user->name}");
    }

    private function shouldProcessInterest(Customer $customer, Carbon $date): bool
    {
        // Only process if customer has outstanding balance in previous month
        $previousMonth = $date->copy()->subMonth();

        // Check if customer had utang sales or existing balance in previous month
        $hasBalance = $customer->sales()
            ->where('payment_type', 'utang')
            ->where('transaction_date', '<=', $previousMonth->endOfMonth())
            ->exists();

        // 80% chance to process interest for customers with balance
        return $hasBalance && rand(1, 100) <= 80;
    }

    private function processMonthlyInterest(User $user, Customer $customer, Carbon $computationDate): void
    {
        // Get the current balance by looking at the most recent customer transaction
        // This ensures we include previous interest in the calculation (compound interest)
        $currentBalance = $this->getCustomerBalance($customer, $computationDate);

        if ($currentBalance <= 0) {
            return;
        }

        // Get customer's interest rate
        $interestRate = $customer->getEffectiveInterestRate();

        // Calculate new balance with interest
        $newBalance = $currentBalance * (1 + $interestRate / 100);
        $interestAmount = $newBalance - $currentBalance;

        // Interest records will be created by CustomerTransactionRebuilderSeeder
    }

    /**
     * Get customer balance at a specific point in time for compound interest calculation.
     * This should be called BEFORE creating the interest record to get proper balance.
     */
    private function getCustomerBalance(Customer $customer, Carbon $computationDate): float
    {
        // Calculate balance from sales and payments up to this date
        $totalSales = $customer->sales()
            ->where('payment_type', 'utang')
            ->where('transaction_date', '<', $computationDate)
            ->sum(\DB::raw('total_amount - paid_amount'));

        $totalPayments = \App\Models\CustomerTransaction::where('customer_id', $customer->id)
            ->where('transaction_type', 'payment')
            ->where('transaction_date', '<', $computationDate)
            ->sum('transaction_amount');

        // Add previous interest from customer transactions
        $totalPreviousInterest = \App\Models\CustomerTransaction::where('customer_id', $customer->id)
            ->where('transaction_type', 'monthly_interest')
            ->where('transaction_date', '<', $computationDate)
            ->sum('transaction_amount');

        return $totalSales - $totalPayments + $totalPreviousInterest;
    }
}
