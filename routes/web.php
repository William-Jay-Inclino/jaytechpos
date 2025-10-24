<?php

use App\Http\Controllers\POS\ProductCategoryController;
use App\Http\Controllers\POS\SaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Should always use named routes for easier maintenance.
| Add Rate limiters to routes as needed.


*/

// Place all routes that need global rate limiting here
Route::middleware(['throttle:global'])->group(function () {

    Route::get('/', function () {
        return Inertia::render('Welcome');
    })->name('home');

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    // Route::get('sales', function () {
    //     return Inertia::render('sales/Index');
    // })->middleware(['auth', 'verified'])->name('sales.index');

    Route::get('sales', [SaleController::class, 'create'])->middleware(['auth', 'verified'])->name('sales');

    Route::post('sales', [SaleController::class, 'store'])->middleware(['auth', 'verified'])->name('sales.store');

    Route::resource('product-categories', ProductCategoryController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
