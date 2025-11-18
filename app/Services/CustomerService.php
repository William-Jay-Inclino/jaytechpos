<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Get all transactions for a customer from the unified customer_transactions table.
     * Returns only base transaction data for performance. Use getTransactionDetails() for full details.
     */
    public function getCustomerTransactions(Customer $customer): Collection
    {
        return $customer->customerTransactions()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // Secondary sort by ID for same timestamp
            ->limit(30)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $this->mapTransactionType($transaction->transaction_type),
                    'transaction_type' => $transaction->transaction_type, // Keep original for detail fetching
                    'reference_id' => $transaction->reference_id,
                    'date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                    'amount' => $transaction->transaction_amount,
                    'formatted_amount' => '₱'.number_format($transaction->transaction_amount, 2),
                    'description' => $transaction->transaction_desc,
                    'previous_balance' => $transaction->previous_balance,
                    'new_balance' => $transaction->new_balance,
                    'formatted_previous_balance' => '₱'.number_format($transaction->previous_balance, 2),
                    'formatted_new_balance' => '₱'.number_format($transaction->new_balance, 2),
                ];
            });
    }

    /**
     * Get detailed transaction data for a specific transaction.
     */
    public function getTransactionDetails(Customer $customer, int $transactionId): ?array
    {
        $transaction = $customer->customerTransactions()->find($transactionId);

        if (! $transaction) {
            return null;
        }

        // Base transaction data
        $data = [
            'id' => $transaction->id,
            'type' => $this->mapTransactionType($transaction->transaction_type),
            'transaction_type' => $transaction->transaction_type,
            'reference_id' => $transaction->reference_id,
            'date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
            'amount' => $transaction->transaction_amount,
            'formatted_amount' => '₱'.number_format($transaction->transaction_amount, 2),
            'description' => $transaction->transaction_desc,
            'previous_balance' => $transaction->previous_balance,
            'new_balance' => $transaction->new_balance,
            'formatted_previous_balance' => '₱'.number_format($transaction->previous_balance, 2),
            'formatted_new_balance' => '₱'.number_format($transaction->new_balance, 2),
        ];

        // Add type-specific detailed data
        if ($transaction->transaction_type === 'sale') {
            $sale = Sale::with(['salesItems.product'])->find($transaction->reference_id);
            if ($sale) {
                $data = array_merge($data, [
                    'invoice_number' => $sale->invoice_number,
                    'payment_type' => $sale->payment_type,
                    'total_amount' => $sale->total_amount,
                    'paid_amount' => $sale->paid_amount,
                    'amount_tendered' => $sale->amount_tendered,
                    'deduct_from_balance' => $sale->deduct_from_balance,
                    'change_amount' => $sale->payment_type === 'cash' && $sale->amount_tendered
                        ? max(0, $sale->amount_tendered - $sale->total_amount - ($sale->deduct_from_balance ?? 0))
                        : null,
                    'notes' => $sale->notes,
                    'sales_items' => $sale->salesItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->product_name ?? 'Unknown Product',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->quantity * $item->unit_price,
                        ];
                    })->toArray(),
                ]);
            }
        } elseif ($transaction->transaction_type === 'utang_payment') {
            // For utang payments, the transaction_desc already contains the notes
            $data['notes'] = $transaction->transaction_desc;
        } elseif ($transaction->transaction_type === 'monthly_interest') {
            // For monthly interest, extract interest rate from description if needed
            if (preg_match('/Interest Rate: ([\d.]+)%/', $transaction->transaction_desc, $matches)) {
                $data['interest_rate'] = (float) $matches[1];
            }
        }

        return $data;
    }

    /**
     * Map database transaction type to frontend type.
     * We now use the original database values as they are more descriptive.
     */
    private function mapTransactionType(string $transactionType): string
    {
        return $transactionType; // No mapping needed - use database values directly
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

        $customers = Customer::where('has_utang', true)->get();

        foreach ($customers as $customer) {
        $totalCustomers = $customers->count();
        $skippedAlreadyApplied = 0;
        $skippedZeroInterest = 0;

        foreach ($customers as $customer) {
            // Skip if monthly interest has already been applied for this customer in the current month
            $alreadyApplied = $customer->customerTransactions()
                ->where('transaction_type', 'monthly_interest')
                ->whereYear('transaction_date', now()->year)
                ->whereMonth('transaction_date', now()->month)
                ->exists();

            if ($alreadyApplied) {
                $skippedAlreadyApplied++;
                continue;
            }

            // Use the model's runningUtangBalance accessor to get current running balance
            // (defined on the Customer model as `runningUtangBalance` -> accessed as `running_utang_balance`).
            $previousBalance = (float) $customer->running_utang_balance;

            // Wrap each customer's processing in a DB transaction for safety
            $transaction = DB::transaction(function () use ($customer, $previousBalance, &$skippedZeroInterest) {
                // Use customer's effective interest rate (falls back to default if null)
                // Normalize stored rate: some installations store percentage values (e.g. 3.00 for 3%),
                // while tests or other code may use decimal fractions (e.g. 0.03). If the rate is > 1,
                // treat it as a percent and divide by 100.
                $interestRate = $customer->getEffectiveInterestRate();
                if ($interestRate > 1) {
                    $interestRate = $interestRate / 100.0;
                }

                // Calculate interest amount (rounded to 2 decimals)
                $interestAmount = (float) round($previousBalance * $interestRate, 2);

                // Skip if there's nothing to apply
                if ($interestAmount <= 0) {
                    $skippedZeroInterest++;
                    return null;
                }

                $newBalance = (float) round($previousBalance + $interestAmount, 2);

                $percent = $interestRate * 100;
                $transactionDesc = sprintf('Monthly interest applied. Interest Rate: %s%%', rtrim(rtrim(number_format($percent, 4, '.', ''), '0'), '.'));

                return $customer->customerTransactions()->create([
                    'user_id' => $customer->user_id ?? auth()->id(),
                    'transaction_type' => 'monthly_interest',
                    'reference_id' => null,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'transaction_desc' => $transactionDesc,
                    'transaction_date' => now(),
                    'transaction_amount' => $interestAmount,
                ]);
            });

            if ($transaction) {
                $created->push($transaction);
            }
        }

        // Write an audit activity summarizing the run
        try {
            $properties = [
                'total_customers' => $totalCustomers,
                'processed' => $created->count(),
                'skipped_already_applied' => $skippedAlreadyApplied,
                'skipped_zero_interest' => $skippedZeroInterest,
                'created_transaction_ids' => $created->pluck('id')->toArray(),
            ];

            // Use Spatie activity logger. Causer may be null when run from scheduler.
            activity()
                ->causedBy(auth()->user() ?? null)
                ->withProperties($properties)
                ->log('Monthly interest processing completed');
        } catch (\Throwable $e) {
            // Don't let audit logging break the main flow. If desired, we could report this to an error tracker.
        }

        return $created;
        }

        return $created;
    }
}
