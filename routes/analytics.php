<?php

use App\Http\Controllers\SiteVisitController;
use Illuminate\Support\Facades\Route;

// Site Analytics
Route::post('site-visit', [SiteVisitController::class, 'store'])
    ->name('analytics.site-visit')
    ->middleware(['throttle:global']);
