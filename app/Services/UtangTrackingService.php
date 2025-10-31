<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\UtangTracking;

class UtangTrackingService
{
    /**
     * Get the active utang tracking for a customer based on current month and year.
     * Returns the latest computation_date record for the current month.
     */
    public function getActiveUtangTracking(int $customerId): ?UtangTracking
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return UtangTracking::where('customer_id', $customerId)
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->latest('computation_date')
            ->first();
    }

    /**
     * Update utang tracking when a sale with utang payment is created.
     */
    public function updateUtangTracking(Sale $sale): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $utangAmount = $sale->total_amount - $sale->paid_amount;
        $customer = Customer::find($sale->customer_id);

        // Find existing utang tracking for current month
        $currentTracking = UtangTracking::where('customer_id', $sale->customer_id)
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->first();

        if ($currentTracking) {
            // Update existing record
            $currentTracking->increment('beginning_balance', $utangAmount);
        } else {
            // Create new record for current month
            $previousBalance = 0;
            $interestRate = $customer->getEffectiveInterestRate();

            // Get the most recent tracking from previous months to calculate starting balance with interest
            $previousTracking = UtangTracking::where('customer_id', $sale->customer_id)
                ->where('computation_date', '<', now()->startOfMonth())
                ->orderBy('computation_date', 'desc')
                ->first();

            if ($previousTracking) {
                // Use customer's current effective interest rate for new calculations
                $interestRate = $customer->getEffectiveInterestRate();
                // Calculate balance with interest from previous month
                $previousBalance = $previousTracking->beginning_balance * (1 + ($interestRate / 100));
            }

            UtangTracking::create([
                'user_id' => $sale->user_id,
                'customer_id' => $sale->customer_id,
                'beginning_balance' => $previousBalance + $utangAmount,
                'computation_date' => now()->startOfMonth(),
                'interest_rate' => $interestRate,
            ]);
        }

        // Update customer's has_utang status
        Customer::where('id', $sale->customer_id)->update(['has_utang' => true]);
    }

    /**
     * Deduct amount from customer's running balance.
     */
    public function deductFromRunningBalance(int $customerId, float $deductionAmount): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Find existing utang tracking for current month
        $currentTracking = UtangTracking::where('customer_id', $customerId)
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->first();

        if ($currentTracking) {
            // Deduct from existing record
            $currentTracking->decrement('beginning_balance', $deductionAmount);

            // If balance is now zero or negative, update customer's has_utang status
            if ($currentTracking->fresh()->beginning_balance <= 0) {
                Customer::where('id', $customerId)->update(['has_utang' => false]);
            }
        } else {
            // Get the most recent tracking from previous months
            $previousTracking = UtangTracking::where('customer_id', $customerId)
                ->where('computation_date', '<', now()->startOfMonth())
                ->orderBy('computation_date', 'desc')
                ->first();

            if ($previousTracking) {
                $customer = Customer::find($customerId);
                $interestRate = $customer->getEffectiveInterestRate();

                // Calculate balance with interest from previous month and subtract deduction
                $balanceWithInterest = $previousTracking->beginning_balance * (1 + ($interestRate / 100));
                $newBalance = $balanceWithInterest - $deductionAmount;

                UtangTracking::create([
                    'user_id' => auth()->id(),
                    'customer_id' => $customerId,
                    'beginning_balance' => max(0, $newBalance), // Ensure non-negative
                    'computation_date' => now()->startOfMonth(),
                    'interest_rate' => $interestRate,
                ]);

                // Update customer's has_utang status
                Customer::where('id', $customerId)->update(['has_utang' => $newBalance > 0]);
            }
        }
    }
}
