<?php

namespace App\Services;

use App\Models\UtangPayment;
use Carbon\Carbon;

class UtangPaymentService
{
    /**
     * Get utang payments for a specific date range and user.
     */
    public function getTotalUtangPayments(string|Carbon $startDate, string|Carbon $endDate, int $userId): float
    {
        $startDate = is_string($startDate) ? Carbon::parse($startDate) : $startDate->copy();
        $endDate = is_string($endDate) ? Carbon::parse($endDate) : $endDate->copy();

        return UtangPayment::where('user_id', $userId)
            ->whereBetween('payment_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->sum('payment_amount');
    }
}
