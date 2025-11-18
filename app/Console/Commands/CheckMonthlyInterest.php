<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

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
        $customers = Customer::where('has_utang', true)->get();

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
            $this->info("{$count} customers need monthly interest processing this month.");
            return 0;
        }

        $this->info('No customers require monthly interest processing this month.');
        return 0;
    }
}
