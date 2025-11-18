<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class ListMonthlyInterestCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utang:list-monthly-candidates {--details : Show balances and computed interest amount} {--limit= : Limit the number of customers returned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List customers that need monthly interest processing this month';

    public function handle(): int
    {
        $showDetails = $this->option('details');

        $customers = Customer::where('has_utang', true)->get();

        $candidates = $customers->filter(function ($customer) {
            $alreadyApplied = $customer->customerTransactions()
                ->where('transaction_type', 'monthly_interest')
                ->whereYear('transaction_date', now()->year)
                ->whereMonth('transaction_date', now()->month)
                ->exists();

            if ($alreadyApplied) {
                return false;
            }

            $previousBalance = (float) $customer->running_utang_balance;
            $rate = $customer->getEffectiveInterestRate();
            if ($rate > 1) {
                $rate = $rate / 100.0;
            }
            $interestAmount = (float) round($previousBalance * $rate, 2);

            return $interestAmount > 0;
        });

        $totalCandidates = $candidates->count();

        $limit = $this->option('limit');
        if ($limit !== null) {
            $limit = (int) $limit;
            $returned = $candidates->take($limit);
        } else {
            $returned = $candidates;
        }

        $count = $returned->count();

        if ($count === 0) {
            $this->info('No customers require monthly interest processing this month.');

            return 0;
        }

        if ($limit !== null) {
            $this->info("Showing {$count} of {$totalCandidates} customers that need monthly interest processing this month (limit={$limit})");
        } else {
            $this->info("Customers that need monthly interest processing this month: ({$count})");
        }

        if ($showDetails) {
            $rows = $returned->map(function ($c) {
                $balance = number_format((float) $c->running_utang_balance, 2);
                $rate = $c->getEffectiveInterestRate();
                if ($rate > 1) {
                    $rate = $rate / 100.0;
                }
                $interest = number_format((float) round($c->running_utang_balance * $rate, 2), 2);

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'mobile' => $c->mobile_number,
                    'balance' => $balance,
                    'interest' => $interest,
                ];
            })->toArray();

            $this->table(['ID', 'Name', 'Mobile', 'Balance', 'Interest'], $rows);
        } else {
            $returned->each(function ($c) {
                $this->line("- {$c->name} (ID: {$c->id})");
            });
        }

        return 0;
    }
}
