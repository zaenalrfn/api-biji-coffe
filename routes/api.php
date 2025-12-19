<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::middleware(['throttle:write'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
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
        return $request->user();
    });

    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->only(['index', 'show']);
    Route::apiResource('products', \App\Http\Controllers\ProductController::class)->only(['index', 'show']);
    Route::apiResource('cart', \App\Http\Controllers\CartController::class)->only(['index', 'show']);
    Route::apiResource('orders', \App\Http\Controllers\OrderController::class)->only(['index', 'show']);
});