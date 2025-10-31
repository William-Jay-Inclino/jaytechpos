<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        // Run monthly utang tracking on 1st of every month at 12:01 AM (Asia/Manila time)
        $schedule->command('utang:process-monthly-tracking')
            ->monthlyOn(1, '00:01')
            ->timezone('Asia/Manila')
            ->withoutOverlapping()
            ->onOneServer();

        // Lightweight daily check to see if monthly tracking is needed
        $schedule->command('utang:check-monthly-tracking')
            ->daily()
            ->at('00:05')
            ->timezone('Asia/Manila')
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
