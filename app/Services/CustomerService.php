<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Collection;

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
}
