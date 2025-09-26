<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Should always use named routes for easier maintenance.
| Add Rate limiters to routes as needed.


*/

Route::middleware(['throttle:global'])->group(function () {
    // Place all routes that need global rate limiting here

    Route::get('/', function () {
        return Inertia::render('Welcome');
    })->name('home');
    
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');


});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
