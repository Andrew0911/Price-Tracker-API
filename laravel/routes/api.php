<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// RATE LIMITER
Route::middleware(['throttle:api'])->group(function () {

    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::get('verify-email', [AuthController::class, 'verifyEmail'])->name('verify.email');

    // Protected routes - require Sanctum authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // Products & Price Alerts
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('products.index');
            Route::post('/', [ProductController::class, 'store'])->name('products.store');
            Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            Route::post('/{product}/alerts', [PriceAlertController::class, 'store'])->name('products.alerts.store');
            Route::delete('/{product}/alerts/{alert}', [PriceAlertController::class, 'destroy'])->name('products.alerts.destroy');
        });

    });
});
