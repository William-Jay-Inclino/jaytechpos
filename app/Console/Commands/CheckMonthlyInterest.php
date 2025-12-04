<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
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
        Log::info('Monthly interest check command started');

        $customers = Customer::where('has_utang', true)->get();
        Log::info("Total customers with utang: {$customers->count()}");

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

        if ($count > 0) {
            Log::info("Monthly interest check: {$count} customers need processing");
            $this->info("{$count} customers need monthly interest processing this month.");

            return 0;
        }

        Log::info('Monthly interest check: No customers need processing');
        $this->info('No customers require monthly interest processing this month.');

        return 0;
    }
}
