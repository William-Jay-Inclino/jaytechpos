<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utang:process-monthly-tracking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly interest for customers with utang';

    public function handle(CustomerService $service): int
    {
        Log::info('Monthly interest processing command started');
        $this->info('Starting monthly interest processing...');

        $totalCustomers = Customer::where('has_utang', true)->count();
        Log::info("Total customers with utang: {$totalCustomers}");

        $created = $service->processMonthlyInterest();

        $processed = $created->count();

        $this->info("Total customers considered: {$totalCustomers}");
        $this->info("Interest transactions created: {$processed}");
        $this->info('A summary activity log has been written to the activity log.');

        Log::info("Monthly interest processing completed. Transactions created: {$processed}");

        return 0;
    }
}
