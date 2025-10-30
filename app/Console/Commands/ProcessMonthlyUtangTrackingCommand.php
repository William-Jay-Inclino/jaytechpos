<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMonthlyUtangTracking;
use App\Models\UtangTracking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProcessMonthlyUtangTrackingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utang:process-monthly-tracking {--force : Force processing even if already run this month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly utang tracking with interest calculation for all customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentMonth = now()->format('Y-m');
        $cacheKey = "monthly_utang_tracking_processed_{$currentMonth}";
        
        // Check if already processed this month (unless forced)
        if (!$this->option('force') && Cache::get($cacheKey)) {
            $this->info("Monthly utang tracking already processed for {$currentMonth}");
            return 0;
        }

        // Check if there are any tracking records for current month
        $existingRecords = UtangTracking::whereMonth('computation_date', now()->month)
            ->whereYear('computation_date', now()->year)
            ->count();

        if (!$this->option('force') && $existingRecords > 0) {
            $this->info("Found {$existingRecords} existing tracking records for current month. Skipping...");
            $this->info("Use --force flag to process anyway");
            return 0;
        }

        $this->info("Starting monthly utang tracking process for {$currentMonth}...");
        
        // Dispatch the job
        ProcessMonthlyUtangTracking::dispatch();
        
        // Mark as processed for this month (expires at end of month)
        $expiresAt = now()->endOfMonth();
        Cache::put($cacheKey, true, $expiresAt);
        
        $this->info("Monthly utang tracking job dispatched successfully");
        $this->info("Process will run in the background");
        
        return 0;
    }
}
