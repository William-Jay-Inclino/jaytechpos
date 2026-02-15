<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function getCustomerTransactions(Customer $customer)
    {
        return $customer->customerTransactions()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();
    }

    /**
     * Process monthly interest for all customers who have utang (debt).
     *
     * For each customer with outstanding balance:
     * - determine the current running balance (last transaction new_balance)
     * - compute interest = runningBalance * interest_rate
     * - create a customer transaction of type `monthly_interest` with previous/new balances
     * - skip creating a transaction when the computed interest amount is zero or negative
     *
     * Returns a collection of created CustomerTransaction models.
     */
    public function processMonthlyInterest(): Collection
    {
        $created = collect();

        $now = now();
        $year = $now->year;
        $month = $now->month;

        // Load customers who have utang, excluding those created in the current month
        $customers = Customer::where('has_utang', true)
            ->where(function ($query) use ($year, $month) {
                $query->whereYear('created_at', '<', $year)
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->whereYear('created_at', '=', $year)
                            ->whereMonth('created_at', '<', $month);
                    });
            })
            ->get();

        if ($customers->isEmpty()) {
            return $created;
        }

        $customerIds = $customers->pluck('id')->all();

        // Pre-fetch which customers already have a monthly_interest this month to avoid N queries.
        $alreadyAppliedIds = DB::table('customer_transactions')
            ->whereIn('customer_id', $customerIds)
            ->where('transaction_type', 'monthly_interest')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->pluck('customer_id')
            ->unique()
            ->all();

        $totalCustomers = count($customerIds);
        $skippedAlreadyApplied = 0;
        $skippedZeroInterest = 0;

        foreach ($customers as $customer) {
            if (in_array($customer->id, $alreadyAppliedIds, true)) {
                $skippedAlreadyApplied++;

                continue;
            }

            $previousBalance = (float) $customer->running_utang_balance;

            // If there's no positive running balance, skip computing interest
            if ($previousBalance <= 0) {
                $skippedZeroInterest++;

                continue;
            }

            $interestRate = $customer->getEffectiveInterestRate();
            if ($interestRate > 1) {
                $interestRate = $interestRate / 100.0;
            }

            $interestAmount = (float) round($previousBalance * $interestRate, 2);

            if ($interestAmount <= 0) {
                $skippedZeroInterest++;

                continue;
            }

            $newBalance = (float) round($previousBalance + $interestAmount, 2);
            $percent = $interestRate * 100;
            $transactionDesc = sprintf('Monthly interest applied. Interest Rate: %s%%', rtrim(rtrim(number_format($percent, 4, '.', ''), '0'), '.'));

            $txn = DB::transaction(function () use ($customer, $previousBalance, $newBalance, $interestAmount, $transactionDesc, $now) {
                return $customer->customerTransactions()->create([
                    'user_id' => $customer->user_id ?? Auth::id(),
                    'transaction_type' => 'monthly_interest',
                    'reference_id' => null,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'transaction_desc' => $transactionDesc,
                    'transaction_date' => $now,
                    'transaction_amount' => $interestAmount,
                ]);
            });

            if ($txn) {
                $created->push($txn);
            }
        }

        // Audit log once for the whole run
        try {
            $properties = [
                'activity_type' => 'monthly_interest_processing',
                'total_customers' => $totalCustomers,
                'processed' => $created->count(),
                'skipped_already_applied' => $skippedAlreadyApplied,
                'skipped_zero_interest' => $skippedZeroInterest,
                'created_transaction_ids' => $created->pluck('id')->toArray(),
            ];

            activity()
                ->causedBy(Auth::user() ?? null)
                ->withProperties($properties)
                ->log('Monthly interest processing completed');
        } catch (\Throwable $e) {
            // Do not let logging failures interrupt the result.
        }

        return $created;
    }
}
