<?php

use Illuminate\Support\Facades\Route;

// Temporary debug route - REMOVE AFTER DEBUGGING
Route::get('/debug-proxy', function () {
    return response()->json([
        'app_url' => config('app.url'),
        'app_env' => config('app.env'),
        'request_scheme' => request()->getScheme(),
        'request_url' => request()->url(),
        'request_full_url' => request()->fullUrl(),
        'is_secure' => request()->secure(),
        'server_port' => request()->getPort(),
        'headers' => [
            'host' => request()->header('Host'),
            'x-forwarded-for' => request()->header('X-Forwarded-For'),
            'x-forwarded-proto' => request()->header('X-Forwarded-Proto'),
            'x-forwarded-host' => request()->header('X-Forwarded-Host'),
            'x-forwarded-port' => request()->header('X-Forwarded-Port'),
            'x-real-ip' => request()->header('X-Real-IP'),
        ],
        'trusted_proxies' => config('trustedproxy.proxies'),
        'session' => [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'cookie_name' => config('session.cookie'),
        ],
        'client_ip' => request()->ip(),
    ]);
})->middleware('web');
