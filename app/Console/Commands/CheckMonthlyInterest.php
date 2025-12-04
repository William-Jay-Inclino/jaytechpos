<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CheckMonthlyInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utang:check-monthly-tracking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if there are customers that need monthly interest processing';

    public function handle(): int
    {
        Log::info('========================================');
        Log::info('[CHECK] Step 1: Monthly interest check started');
        Log::info("[CHECK] Current date: ".now()->format('Y-m-d H:i:s'));

        $customers = Customer::where('has_utang', true)->get();
        Log::info("[CHECK] Step 2: Found {$customers->count()} customers with utang");

        $needsProcessing = $customers->filter(function ($customer) {
            $alreadyApplied = $customer->customerTransactions()
                ->where('transaction_type', 'monthly_interest')
                ->whereYear('transaction_date', now()->year)
                ->whereMonth('transaction_date', now()->month)
                ->exists();

            if ($alreadyApplied) {
                return false;
            }

            $previousBalance = (float) $customer->running_utang_balance;
            $interestAmount = (float) round($previousBalance * $customer->getEffectiveInterestRate(), 2);

            return $interestAmount > 0;
        });

        $count = $needsProcessing->count();
        Log::info("[CHECK] Step 3: After filtering, {$count} customers need processing");

        if ($count > 0) {
            $this->info("{$count} customers need monthly interest processing this month.");
            
            Log::info('[CHECK] Step 4: Starting automatic processing...');
            $this->info('Triggering monthly interest processing...');
            
            Artisan::call('utang:process-monthly-tracking');
            
            Log::info('[CHECK] Step 5: Processing command completed');
            Log::info('[CHECK] ✓ Monthly interest check finished successfully');
            Log::info('========================================');
            $this->info('Monthly interest processing completed.');

            return 0;
        }

        Log::info('[CHECK] Step 4: No customers require processing this month');
        Log::info('[CHECK] ✓ Monthly interest check finished - no action needed');
        Log::info('========================================');
        $this->info('No customers require monthly interest processing this month.');

        return 0;
    }
}
