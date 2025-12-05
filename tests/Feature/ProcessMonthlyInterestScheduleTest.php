<?php

use Illuminate\Support\Facades\Artisan;

it('is registered in the schedule', function () {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    expect($output)->toContain('utang:process-monthly-tracking');
});

it('is scheduled with the correct cron expression for 1st day at 02:00', function () {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    // The cron expression for 1st day of month at 02:00 is: 0 2 1 * *
    // Format in schedule:list: "0 2 1 * *  php artisan utang:process-monthly-tracking"
    expect($output)
        ->toContain('utang:process-monthly-tracking')
        ->and($output)->toMatch('/0\s+2\s+1\s+\*\s+\*.*utang:process-monthly-tracking/');
});

it('can be run successfully with schedule:test', function () {
    Artisan::call('schedule:test', [
        '--name' => 'utang:process-monthly-tracking',
    ]);

    $output = Artisan::output();

    expect($output)
        ->toContain('utang:process-monthly-tracking')
        ->and($output)->toContain('Running');
});
