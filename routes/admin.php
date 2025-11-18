<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Profile Management (admin prefix to avoid conflict)
Route::prefix('admin/profile')->name('admin.profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('password');
});

// User Management
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});

// Analytics
// Route::prefix('analytics')->name('analytics.')->group(function () {
//     Route::get('/', [AnalyticsController::class, 'index'])->name('index');
//     Route::get('/sales', [AnalyticsController::class, 'sales'])->name('sales');
//     Route::get('/products', [AnalyticsController::class, 'products'])->name('products');
//     Route::get('/customers', [AnalyticsController::class, 'customers'])->name('customers');
// });

// Activity Logs
Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    Route::get('/{log}', [ActivityLogController::class, 'show'])->name('show');
});
