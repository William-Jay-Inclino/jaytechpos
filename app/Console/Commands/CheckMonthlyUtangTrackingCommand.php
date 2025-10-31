<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonthlyUtangTrackingNeeded;
use Illuminate\Console\Command;

class CheckMonthlyUtangTrackingCommand extends Command
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
    protected $description = 'Check if monthly utang tracking needs to run';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking if monthly utang tracking is needed...');
        
        // Dispatch the lightweight checker job
        CheckMonthlyUtangTrackingNeeded::dispatch();
        
        $this->info('Monthly utang tracking check dispatched.');
        
        return self::SUCCESS;
    }
}
