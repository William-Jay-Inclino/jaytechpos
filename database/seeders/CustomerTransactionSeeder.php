<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Models\UtangPayment;
use App\Models\UtangTracking;
use Illuminate\Database\Seeder;

class CustomerTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', '!=', UserRole::Admin->value)->get();

        foreach ($users as $user) {
            $this->createCustomerTransactionsForUser($user);
        }
    }

    private function createCustomerTransactionsForUser(User $user): void
    {
        $transactionCount = 0;

        // Process Sales transactions
        $sales = Sale::where('user_id', $user->id)
            ->whereNotNull('customer_id')
            ->with('customer')
            ->orderBy('transaction_date')
            ->get();

        foreach ($sales as $sale) {
            // Calculate customer balance before this sale
            $previousBalance = $this->getCustomerBalanceBeforeTransaction($sale->customer, $sale->transaction_date);

            // Calculate new balance after sale
            if ($sale->payment_type === 'utang') {
                $unpaidAmount = $sale->total_amount - $sale->paid_amount;
                $newBalance = $previousBalance + $unpaidAmount;
            } else {
                $newBalance = $previousBalance;
            }

            CustomerTransaction::create([
                'user_id' => $user->id,
                'customer_id' => $sale->customer_id,
                'transaction_type' => 'sale',
                'reference_id' => $sale->id,
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'transaction_desc' => $sale->invoice_number,
                'transaction_date' => $sale->transaction_date,
                'transaction_amount' => $sale->total_amount,
            ]);

            $transactionCount++;
        }

        // Process Utang Payment transactions
        $payments = UtangPayment::where('user_id', $user->id)
            ->with('customer')
            ->orderBy('payment_date')
            ->get();

        foreach ($payments as $payment) {
            // Calculate customer balance before this payment
            $previousBalance = $this->getCustomerBalanceBeforeTransaction($payment->customer, $payment->payment_date);
            $newBalance = $previousBalance - $payment->payment_amount;

            CustomerTransaction::create([
                'user_id' => $user->id,
                'customer_id' => $payment->customer_id,
                'transaction_type' => 'utang_payment',
                'reference_id' => $payment->id,
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'transaction_desc' => $payment->notes ?: 'Payment',
                'transaction_date' => $payment->payment_date,
                'transaction_amount' => $payment->payment_amount,
            ]);

            $transactionCount++;
        }

        // Monthly Interest transactions are already created by MonthlyInterestSeeder

        $this->command->info("Created {$transactionCount} customer transactions for user: {$user->name}");
    }

    private function getCustomerBalanceBeforeTransaction(Customer $customer, $transactionDate): float
    {
        // Calculate balance from all transactions before this date
        $totalSales = $customer->sales()
            ->where('payment_type', 'utang')
            ->where('transaction_date', '<', $transactionDate)
            ->sum(\DB::raw('total_amount - paid_amount'));

        $totalPayments = $customer->utangPayments()
            ->where('payment_date', '<', $transactionDate)
            ->sum('payment_amount');

        // Add any interest from UtangTracking
        $totalInterest = UtangTracking::where('customer_id', $customer->id)
            ->where('computation_date', '<', $transactionDate)
            ->sum(\DB::raw('beginning_balance - (
                SELECT COALESCE(SUM(total_amount - paid_amount), 0) 
                FROM sales 
                WHERE customer_id = utang_trackings.customer_id 
                AND payment_type = \'utang\' 
                AND transaction_date < utang_trackings.computation_date
            ) - (
                SELECT COALESCE(SUM(payment_amount), 0) 
                FROM utang_payments 
                WHERE customer_id = utang_trackings.customer_id 
                AND payment_date < utang_trackings.computation_date
            )'));

        return $totalSales - $totalPayments + $totalInterest;
    }
}
