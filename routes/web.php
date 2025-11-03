<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\POS\CustomerController;
use App\Http\Controllers\POS\CustomerTransactionController;
use App\Http\Controllers\POS\ExpenseController;
use App\Http\Controllers\POS\ProductCategoryController;
use App\Http\Controllers\POS\ProductController;
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

    Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

    // sales
    Route::get('sales', [SaleController::class, 'index'])->middleware(['auth', 'verified'])->name('sales');
    Route::post('sales', [SaleController::class, 'store'])->middleware(['auth', 'verified'])->name('sales.store');

    // utang payments (now handled by CustomerTransactionController)
    Route::get('utang-payments', [CustomerTransactionController::class, 'utangPayments'])->middleware(['auth', 'verified'])->name('utang-payments');
    Route::post('utang-payments', [CustomerTransactionController::class, 'storeUtangPayment'])->middleware(['auth', 'verified'])->name('utang-payments.store');

    // customers
    Route::get('customers/{customer}/transactions', [CustomerController::class, 'transactions'])->middleware(['auth', 'verified'])->name('customers.transactions');
    Route::resource('customers', CustomerController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // products
    Route::resource('products', ProductController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // expenses
    Route::resource('expenses', ExpenseController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // API endpoints (JSON responses for AJAX calls)
    Route::prefix('api')->middleware(['auth', 'verified'])->group(function () {
        // Dashboard API endpoints
        Route::get('dashboard/cash-flow', [DashboardController::class, 'getCashFlowData'])->name('dashboard.api.cash-flow');
        Route::get('dashboard/sales-chart', [DashboardController::class, 'getSalesChartDataForYear'])->name('dashboard.api.sales-chart');
        Route::get('dashboard/best-selling-products', [DashboardController::class, 'getBestSellingProductsForYear'])->name('dashboard.api.best-selling-products');

        // Customer API endpoints
        Route::get('customers/{customer}/transactions', [CustomerController::class, 'getTransactions'])->name('customers.api.transactions');
        Route::get('customers/{customer}/transactions/{transactionId}', [CustomerController::class, 'getTransactionDetails'])->name('customers.api.transaction-details');

        // Product Category API endpoints
        Route::get('product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.api.index');
        Route::get('product-categories/active', [ProductCategoryController::class, 'active'])->name('product-categories.api.active');
        Route::post('product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.api.store');
        Route::put('product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('product-categories.api.update');
        Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('product-categories.api.destroy');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
