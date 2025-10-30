<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\UtangTracking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyUtangTracking implements ShouldQueue
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
     */
    public function handle(): void
    {
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        Log::info('Starting monthly utang tracking process');

        // Get all customers who have utang
        $customers = Customer::where('has_utang', true)->get();

        foreach ($customers as $customer) {
            try {
                DB::transaction(function () use ($customer, &$processedCount, &$skippedCount) {
                    $currentDate = now();
                    $currentMonth = $currentDate->month;
                    $currentYear = $currentDate->year;

                    // Check if record already exists for current month
                    $existingRecord = UtangTracking::where('customer_id', $customer->id)
                        ->whereMonth('computation_date', $currentMonth)
                        ->whereYear('computation_date', $currentYear)
                        ->first();

                    if ($existingRecord) {
                        $skippedCount++;
                        Log::debug("Skipping customer {$customer->id} - record already exists for current month");
                        return;
                    }

                    // Get current running balance
                    $currentBalance = $customer->running_utang_balance;

                    // Skip if balance is zero
                    if ($currentBalance <= 0) {
                        $skippedCount++;
                        Log::debug("Skipping customer {$customer->id} - zero balance");
                        return;
                    }

                    // Get effective interest rate
                    $interestRate = $customer->getEffectiveInterestRate();

                    // Calculate new balance with interest (simple interest)
                    $newBalance = $currentBalance * (1 + $interestRate / 100);

                    // Create new utang tracking record
                    UtangTracking::create([
                        'user_id' => $customer->user_id,
                        'customer_id' => $customer->id,
                        'beginning_balance' => $newBalance,
                        'computation_date' => $currentDate->startOfMonth(),
                        'interest_rate' => $interestRate,
                    ]);

                    $processedCount++;
                    Log::debug("Processed customer {$customer->id}: {$currentBalance} -> {$newBalance} at {$interestRate}%");
                });
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error processing customer {$customer->id}: " . $e->getMessage());
            }
        }

        Log::info("Monthly utang tracking completed", [
            'total_customers' => $customers->count(),
            'processed' => $processedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount,
        ]);
    }
}
