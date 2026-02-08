<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\POS\CustomerController;
use App\Http\Controllers\POS\CustomerTransactionController;
use App\Http\Controllers\POS\ExpenseController;
// use App\Http\Controllers\POS\ProductCategoryController;
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

    Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'redirect-if-admin'])->name('dashboard');

    // sales
    Route::get('sales', [SaleController::class, 'index'])->middleware(['auth', 'verified'])->name('sales');
    Route::post('sales', [SaleController::class, 'store'])->middleware(['auth', 'verified'])->name('sales.store');

    // inventory
    Route::get('inventory', [InventoryController::class, 'index'])->middleware(['auth', 'verified'])->name('inventory');

    // sales reports
    Route::get('sales-report', [App\Http\Controllers\SalesReportController::class, 'index'])->middleware(['auth', 'verified'])->name('sales-report');

    // utang payments (now handled by CustomerTransactionController)
    Route::get('utang-payments', [CustomerTransactionController::class, 'utangPayments'])->middleware(['auth', 'verified'])->name('utang-payments');
    Route::post('utang-payments', [CustomerTransactionController::class, 'storeUtangPayment'])->middleware(['auth', 'verified'])->name('utang-payments.store');

    // customers
    Route::get('customers/{customer}/transactions', [CustomerController::class, 'transactions'])->middleware(['auth', 'verified'])->name('customers.transactions');
    Route::patch('customers/{customer}/balance', [CustomerController::class, 'updateBalance'])->middleware(['auth', 'verified'])->name('customers.update-balance');
    Route::resource('customers', CustomerController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // products
    Route::resource('products', ProductController::class)
        ->middleware(['auth', 'verified'])
        ->except(['show']);

    // expenses
    Route::get('expenses/analytics', [ExpenseController::class, 'analytics'])->middleware(['auth', 'verified'])->name('expenses.analytics');
    Route::get('expenses/category/{categoryId}', [ExpenseController::class, 'categoryExpenses'])->middleware(['auth', 'verified'])->name('expenses.category');
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

        // Sale API endpoints
        Route::get('sales/{sale}', [SaleController::class, 'getSale'])->name('sales.api.show');
        Route::get('sales/products/search', [SaleController::class, 'searchProducts'])->name('sales.api.products.search');
        Route::get('customers/{customer}/balance', [SaleController::class, 'getCustomerBalance'])->name('customers.api.balance');

        // Sales Report API endpoints
        Route::get('sales-report/data', [App\Http\Controllers\SalesReportController::class, 'getSalesData'])->name('sales-report.api.data');
        Route::get('sales-report/chart', [App\Http\Controllers\SalesReportController::class, 'getChartData'])->name('sales-report.api.chart');
        Route::get('sales-report/payment-types', [App\Http\Controllers\SalesReportController::class, 'getPaymentTypeData'])->name('sales-report.api.payment-types');

        // Stock Movement API endpoints
        Route::post('stock-movements/stock-in', [App\Http\Controllers\Inventory\StockMovementController::class, 'stockIn'])->name('stock-movements.api.stock-in');
        Route::post('stock-movements/stock-out', [App\Http\Controllers\Inventory\StockMovementController::class, 'stockOut'])->name('stock-movements.api.stock-out');
        Route::post('stock-movements/adjustment', [App\Http\Controllers\Inventory\StockMovementController::class, 'stockAdjustment'])->name('stock-movements.api.adjustment');
        Route::get('stock-movements/product/{productId}', [App\Http\Controllers\Inventory\StockMovementController::class, 'getProductStockMovements'])->name('stock-movements.api.product');

        // Inventory API endpoints
        Route::post('inventory/update-low-stock-threshold', [App\Http\Controllers\Inventory\InventoryController::class, 'updateLowStockThreshold'])->name('inventory.api.update-low-stock-threshold');
    });

    Route::prefix('analytics')->group(function () {
        require __DIR__.'/analytics.php';
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
