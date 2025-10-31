<?php

use App\Http\Controllers\POS\CustomerController;
use App\Http\Controllers\POS\ProductCategoryController;
use App\Http\Controllers\POS\ProductController;
use App\Http\Controllers\POS\SaleController;
use App\Http\Controllers\POS\UtangPaymentController;
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

    // sales
    Route::get('sales', [SaleController::class, 'index'])->middleware(['auth', 'verified'])->name('sales');
    Route::post('sales', [SaleController::class, 'store'])->middleware(['auth', 'verified'])->name('sales.store');

    // utang payments
    Route::get('utang-payments', [UtangPaymentController::class, 'index'])->middleware(['auth', 'verified'])->name('utang-payments');
    Route::post('utang-payments', [UtangPaymentController::class, 'store'])->middleware(['auth', 'verified'])->name('utang-payments.store');

    // customers
    Route::get('customers/{customer}/transactions', [CustomerController::class, 'transactions'])->middleware(['auth', 'verified'])->name('customers.transactions');
    Route::get('api/customers/{customer}/transactions', [CustomerController::class, 'getTransactions'])->middleware(['auth', 'verified'])->name('customers.api.transactions');
    Route::resource('customers', CustomerController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // product categories
    Route::resource('product-categories', ProductCategoryController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // products
    Route::resource('products', ProductController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
