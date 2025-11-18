<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\UserRole;

class CustomerTransactionRebuilderSeeder extends Seeder
{
    /**
     * Rebuild customer transactions in chronological order.
     */
    public function run(): void
    {
        // First, clear existing customer transactions
        CustomerTransaction::truncate();

        $users = User::where('role', '!=', UserRole::Admin->value)->get();

        foreach ($users as $user) {
            if($user->role === UserRole::Admin) {
                continue;
            }
            $this->rebuildCustomerTransactionsForUser($user);
        }
    }

    private function rebuildCustomerTransactionsForUser(User $user): void
    {
        $customers = Customer::where('user_id', $user->id)->get();
        $transactionCount = 0;

        foreach ($customers as $customer) {
            $transactionCount += $this->rebuildTransactionsForCustomer($user, $customer);
        }

        $this->command->info("Rebuilt {$transactionCount} customer transactions for user: {$user->name}");
    }

    private function rebuildTransactionsForCustomer(User $user, Customer $customer): int
    {
        // Collect all transactions for this customer
        $allTransactions = collect();

        // Add sales
        $sales = Sale::where('user_id', $user->id)
            ->where('customer_id', $customer->id)
            ->where('payment_type', 'utang')
            ->get();

        foreach ($sales as $sale) {
            $allTransactions->push([
                'type' => 'sale',
                'date' => $sale->transaction_date,
                'data' => $sale,
                'amount' => $sale->total_amount - $sale->paid_amount, // Unpaid amount
            ]);
        }

        // Add existing payments from CustomerTransaction (skip for fresh rebuild)
        // Since we're rebuilding from scratch and payments will be added via the new system,
        // we don't need to process existing payment records here

        // Generate monthly interest transactions for this customer
        $this->generateMonthlyInterest($user, $customer, $allTransactions);

        // Sort all transactions by date
        $sortedTransactions = $allTransactions->sortBy([
            ['date', 'asc'],
            ['type', 'asc'], // If same date, process in order: sale, payment, interest
        ]);

        // Process transactions in chronological order
        $runningBalance = 0;
        $createdCount = 0;

        foreach ($sortedTransactions as $transaction) {
            $previousBalance = $runningBalance;
            $runningBalance += $transaction['amount'];

            // Create customer transaction record
            $this->createCustomerTransactionRecord(
                $user,
                $customer,
                $transaction,
                $previousBalance,
                $runningBalance
            );

            $createdCount++;
        }

        return $createdCount;
    }

    private function createCustomerTransactionRecord(User $user, Customer $customer, array $transaction, float $previousBalance, float $newBalance): void
    {
        $data = $transaction['data'];

        switch ($transaction['type']) {
            case 'sale':
                CustomerTransaction::create([
                    'user_id' => $user->id,
                    'customer_id' => $customer->id,
                    'transaction_type' => 'sale',
                    'transaction_date' => $data->transaction_date,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'transaction_desc' => "Sale #{$data->invoice_number}",
                    'reference_id' => $data->id,
                    'transaction_amount' => $data->total_amount,
                ]);
                break;

            case 'payment':
                // Payment processing will be handled by the new CustomerTransactionController
                // This case should not occur in the current rebuilder
                break;

            case 'interest':
                CustomerTransaction::create([
                    'user_id' => $user->id,
                    'customer_id' => $customer->id,
                    'transaction_type' => 'monthly_interest',
                    'transaction_date' => $data->computation_date,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'transaction_desc' => 'Monthly Interest ('.number_format($data->interest_rate, 2).'%)',
                    'reference_id' => null,
                    'transaction_amount' => $transaction['amount'],
                ]);
                break;
        }
    }

    private function generateMonthlyInterest(User $user, Customer $customer, $allTransactions): void
    {
        if (! $customer->has_utang) {
            return;
        }

        // Generate interest for months February to November 2025
        for ($month = 2; $month <= 11; $month++) {
            $computationDate = \Carbon\Carbon::create(2025, $month, 1, 9, 0, 0);

            // 80% chance to generate interest (same as original MonthlyInterestSeeder)
            if (rand(1, 100) <= 80) {
                // Calculate current balance at this date from existing transactions
                $currentBalance = $this->calculateBalanceAtDate($allTransactions, $computationDate);

                if ($currentBalance > 0) {
                    $interestRate = $customer->getEffectiveInterestRate();
                    $newBalance = $currentBalance * (1 + $interestRate / 100);
                    $interestAmount = $newBalance - $currentBalance;

                    $allTransactions->push([
                        'type' => 'interest',
                        'date' => $computationDate,
                        'data' => (object) [
                            'interest_rate' => $interestRate,
                            'computation_date' => $computationDate,
                        ],
                        'amount' => $interestAmount,
                    ]);
                }
            }
        }
    }

    private function calculateBalanceAtDate($transactions, $computationDate): float
    {
        $balance = 0;

        foreach ($transactions as $transaction) {
            if ($transaction['date'] < $computationDate) {
                $balance += $transaction['amount'];
            }
        }

        return $balance;
    }
}
