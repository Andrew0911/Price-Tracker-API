<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// RATE LIMITER
Route::middleware(['throttle:api'])->group(function () {

    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset-password/validate', [AuthController::class, 'validateResetPasswordToken'])->name('password.validate');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->name('verify.email');

    // Protected routes - require Sanctum authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::patch('user', [AuthController::class, 'updateUser'])->name('user.update');
        Route::delete('account', [AuthController::class, 'deleteAccount'])->name('account.delete');

        // Admin users management
        Route::prefix('admin/users')->group(function () {
            Route::get('/', [AdminUsersController::class, 'index'])->name('admin.users.index');
            Route::get('/{user}', [AdminUsersController::class, 'show'])->name('admin.users.show');
            Route::patch('/{user}/promote', [AdminUsersController::class, 'promote'])->name('admin.users.promote');
            Route::delete('/{user}', [AdminUsersController::class, 'destroy'])->name('admin.users.destroy');
        });

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // Users & Friends
        Route::get('users', [FriendsController::class, 'users'])->name('users.index');
        Route::prefix('friends')->group(function () {
            Route::get('/', [FriendsController::class, 'index'])->name('friends.index');
            Route::get('/requests', [FriendsController::class, 'requests'])->name('friends.requests');
            Route::post('/{user}', [FriendsController::class, 'sendRequest'])->name('friends.send');
            Route::post('/{user}/accept', [FriendsController::class, 'accept'])->name('friends.accept');
            Route::delete('/{user}/decline', [FriendsController::class, 'decline'])->name('friends.decline');
            Route::delete('/{user}', [FriendsController::class, 'remove'])->name('friends.remove');
        });

        // Products & Price Alerts
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('products.index');
            Route::post('/', [ProductController::class, 'store'])->name('products.store');
            Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            Route::post('/{product}/alerts', [PriceAlertController::class, 'store'])->name('products.alerts.store');
            Route::delete('/{product}/alerts/{alert}', [PriceAlertController::class, 'destroy'])->name('products.alerts.destroy');
        });

        // Messages
        Route::prefix('messages')->group(function () {
            Route::get('/unread-counts', [MessagesController::class, 'unreadCounts'])->name('messages.unread_counts');
            Route::get('/{user}', [MessagesController::class, 'index'])->name('messages.index');
            Route::post('/{user}', [MessagesController::class, 'store'])->name('messages.store');
        });

    });
});
