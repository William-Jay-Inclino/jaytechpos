<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAdmin;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
            
            // Temporary debug route - REMOVE AFTER DEBUGGING
            if (file_exists(base_path('routes/debug.php'))) {
                require base_path('routes/debug.php');
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            TrustProxies::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'redirect-if-admin' => RedirectIfAdmin::class,
        ]);
    })
    ->withSchedule(function ($schedule) {

        // Lightweight daily check to see if monthly tracking is needed
        $schedule->command('utang:check-monthly-tracking')
            ->daily()
            ->at('08:00')
            ->timezone('Asia/Manila')
            ->withoutOverlapping()
            ->onOneServer();
            
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
