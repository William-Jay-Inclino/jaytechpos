<?php

namespace App\Services;

use App\Models\Sale;

class SaleService
{
    /**
     * Generate a unique invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.date('Y').'-';

        // Get all sales with the current year prefix and extract numbers
        $existingNumbers = Sale::where('invoice_number', 'like', $prefix.'%')
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($prefix) {
                $numberPart = substr($invoiceNumber, strlen($prefix));

                return (int) $numberPart;
            })
            ->max();

        $nextNumber = $existingNumbers ? $existingNumbers + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
