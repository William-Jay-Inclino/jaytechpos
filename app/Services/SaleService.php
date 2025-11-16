<?php

namespace App\Services;

use App\Models\Sale;
use Carbon\Carbon;

class SaleService
{
    /**
     * Generate a unique invoice number for a specific user.
     */
    public function generateInvoiceNumber(int $userId): string
    {
        $prefix = 'INV-'.date('Y').'-';

        // Get all sales for this user with the current year prefix and extract numbers
        $existingNumbers = Sale::where('user_id', $userId)
            ->where('invoice_number', 'like', $prefix.'%')
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($prefix) {
                $numberPart = substr($invoiceNumber, strlen($prefix));

                return (int) $numberPart;
            })
            ->max();

        $nextNumber = $existingNumbers ? $existingNumbers + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get total sales for a specific date range and user.
     * Optionally filter by payment type.
     */
    public function getTotalSales(string|Carbon $startDate, string|Carbon $endDate, int $userId, ?string $paymentType = null): float
    {
        $startDate = is_string($startDate) ? Carbon::parse($startDate) : $startDate->copy();
        $endDate = is_string($endDate) ? Carbon::parse($endDate) : $endDate->copy();

        $query = Sale::where('user_id', $userId)
            ->whereBetween('transaction_date', [$startDate->startOfDay(), $endDate->endOfDay()]);

        if ($paymentType !== null) {
            $query->where('payment_type', $paymentType);
        }

        return $query->sum('total_amount');
    }
}
