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
        Log::info('----------------------------------------');
        Log::info('[PROCESS] Step 1: Monthly interest processing started');
        Log::info("[PROCESS] Current date: ".now()->format('Y-m-d H:i:s'));
        $this->info('Starting monthly interest processing...');

        $totalCustomers = Customer::where('has_utang', true)->count();
        Log::info("[PROCESS] Step 2: Found {$totalCustomers} customers with utang");

        Log::info('[PROCESS] Step 3: Calling CustomerService->processMonthlyInterest()');
        $created = $service->processMonthlyInterest();

        $processed = $created->count();
        Log::info("[PROCESS] Step 4: Service completed - {$processed} transactions created");

        $this->info("Total customers considered: {$totalCustomers}");
        $this->info("Interest transactions created: {$processed}");
        $this->info('A summary activity log has been written to the activity log.');

        Log::info("[PROCESS] ✓ Monthly interest processing finished successfully");
        Log::info("[PROCESS] Summary: {$processed} out of {$totalCustomers} customers processed");
        Log::info('----------------------------------------');

        return 0;
    }
}
