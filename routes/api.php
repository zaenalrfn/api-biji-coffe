<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::middleware(['throttle:write'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public Midtrans Webhook
    Route::post('/midtrans/callback', [\App\Http\Controllers\MidtransCallbackController::class, 'handle']);

    // Password Reset
    Route::post('/forgot-password', [\App\Http\Controllers\ResetPasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\ResetPasswordController::class, 'resetPassword']);

    // Public Banner Routes
    Route::get('/banners', [\App\Http\Controllers\BannerController::class, 'index']);
    Route::get('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware(['throttle:write'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user', [AuthController::class, 'updateProfile']);

        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->except(['index', 'show']);
        Route::apiResource('products', \App\Http\Controllers\ProductController::class)->except(['index', 'show']);
        Route::apiResource('cart', \App\Http\Controllers\CartController::class)->except(['index', 'show']);
        Route::apiResource('orders', \App\Http\Controllers\OrderController::class)->except(['index', 'show']);
    });

    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json(array_merge($user->toArray(), [
            'roles' => $user->getRoleNames()
        ]));
    });

    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->only(['index', 'show']);
    Route::apiResource('products', \App\Http\Controllers\ProductController::class)->only(['index', 'show']);
    Route::apiResource('cart', \App\Http\Controllers\CartController::class)->only(['index', 'show']);
    Route::apiResource('orders', \App\Http\Controllers\OrderController::class)->only(['index', 'show']);

    Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index']);
    Route::post('/wishlist', [\App\Http\Controllers\WishlistController::class, 'store']);
    Route::delete('/wishlist/{product_id}', [\App\Http\Controllers\WishlistController::class, 'destroy']);

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/delete-all', [\App\Http\Controllers\NotificationController::class, 'deleteAll']);

    // Public Store Routes (View Only)
    Route::get('/stores', [\App\Http\Controllers\StoreController::class, 'index']);
    Route::get('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'show']);

    // Protected Routes (Admin)
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/admin/users/{user}/promote', [\App\Http\Controllers\AdminController::class, 'promote']);

        // Banner Management
        Route::post('/banners', [\App\Http\Controllers\BannerController::class, 'store']);
        Route::post('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'update']);
        Route::delete('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'destroy']);

        // Store Management
        Route::post('/stores', [\App\Http\Controllers\StoreController::class, 'store']);
        Route::post('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'update']);
        Route::delete('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'destroy']);
    });
});