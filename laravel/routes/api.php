<?php

use App\Http\Controllers\AuthController;
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
    });
});
