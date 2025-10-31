<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\UtangTracking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckMonthlyUtangTrackingNeeded implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Lightweight check to see if monthly utang tracking needs to run
     */
    public function handle(): void
    {
        $currentDate = now();
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;

        Log::info('Checking if monthly utang tracking is needed', [
            'month' => $currentMonth,
            'year' => $currentYear,
        ]);

        // Only run if we're on or after the 1st of the month
        if ($currentDate->day < 1) {
            Log::info('Too early in the month to run utang tracking');
            return;
        }

        // Get all customers who should have utang tracking records
        $customersWithUtang = Customer::where('has_utang', true)->get();
        
        if ($customersWithUtang->isEmpty()) {
            Log::info('No customers with utang found - nothing to process');
            return;
        }

        // Count customers who already have records created this month
        $processedCustomerIds = UtangTracking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->pluck('customer_id')
            ->unique();

        $totalCustomersWithUtang = $customersWithUtang->count();
        $processedCustomersCount = $processedCustomerIds->count();

        Log::info('Monthly utang tracking status check', [
            'total_customers_with_utang' => $totalCustomersWithUtang,
            'processed_customers' => $processedCustomersCount,
            'completion_percentage' => $totalCustomersWithUtang > 0 ? round(($processedCustomersCount / $totalCustomersWithUtang) * 100, 2) : 0,
        ]);

        // Check if all customers with utang have been processed
        if ($processedCustomersCount >= $totalCustomersWithUtang) {
            Log::info('Monthly utang tracking already completed for all customers this month');
            return;
        }

        // Find which customers still need processing
        $unprocessedCustomerIds = $customersWithUtang->pluck('id')->diff($processedCustomerIds);
        
        Log::info('Monthly utang tracking needed - some customers not processed yet', [
            'unprocessed_customer_ids' => $unprocessedCustomerIds->toArray(),
            'unprocessed_count' => $unprocessedCustomerIds->count(),
        ]);
        
        // Dispatch the heavy job
        ProcessMonthlyUtangTracking::dispatch();
    }
}
