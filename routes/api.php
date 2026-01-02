<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Public Routes (Throttle Write)
|--------------------------------------------------------------------------
*/
Route::middleware(['throttle:write'])->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Google OAuth
    Route::get('oauth/google', [\App\Http\Controllers\OauthController::class, 'redirectToProvider'])
        ->name('oauth.google');
    Route::get('oauth/google/callback', [\App\Http\Controllers\OauthController::class, 'handleProviderCallback'])
        ->name('oauth.google.callback');

    // Midtrans Webhook
    Route::post('/midtrans/callback', [\App\Http\Controllers\MidtransCallbackController::class, 'handle']);

    // Password Reset
    Route::post('/forgot-password', [\App\Http\Controllers\ResetPasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\ResetPasswordController::class, 'resetPassword']);

    // Public Banner
    Route::get('/banners', [\App\Http\Controllers\BannerController::class, 'index']);
    Route::get('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Write Actions (Throttle Write)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:write'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user', [AuthController::class, 'updateProfile']);

        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)
            ->except(['index', 'show']);
        Route::apiResource('products', \App\Http\Controllers\ProductController::class)
            ->except(['index', 'show']);
        Route::apiResource('cart', \App\Http\Controllers\CartController::class)
            ->except(['index', 'show']);
        Route::apiResource('orders', \App\Http\Controllers\OrderController::class)
            ->except(['index', 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated User Info
    |--------------------------------------------------------------------------
    */
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json(array_merge(
            $user->toArray(),
            ['roles' => $user->getRoleNames()]
        ));
    });

    /*
    |--------------------------------------------------------------------------
    | Read Only Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)
        ->only(['index', 'show']);
    Route::apiResource('products', \App\Http\Controllers\ProductController::class)
        ->only(['index', 'show']);
    Route::apiResource('cart', \App\Http\Controllers\CartController::class)
        ->only(['index', 'show']);
    Route::apiResource('orders', \App\Http\Controllers\OrderController::class)
        ->only(['index', 'show']);

    /*
    |--------------------------------------------------------------------------
    | Chat Order ✅
    |--------------------------------------------------------------------------
    */
    Route::get('/orders/{id}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages']);
    Route::post('/orders/{id}/messages', [\App\Http\Controllers\ChatController::class, 'sendMessage']);
    Route::get('/chat-list', [\App\Http\Controllers\ChatController::class, 'getChatList']);
    Broadcast::routes(['middleware' => ['auth:sanctum']]);

    /*
    |--------------------------------------------------------------------------
    | Wishlist
    |--------------------------------------------------------------------------
    */
    Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index']);
    Route::post('/wishlist', [\App\Http\Controllers\WishlistController::class, 'store']);
    Route::delete('/wishlist/{product_id}', [\App\Http\Controllers\WishlistController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/delete-all', [\App\Http\Controllers\NotificationController::class, 'deleteAll']);

    /*
    |--------------------------------------------------------------------------
    | Points & Rewards
    |--------------------------------------------------------------------------
    */
    Route::get('/points', [\App\Http\Controllers\PointController::class, 'index']);
    Route::get('/rewards', [\App\Http\Controllers\RewardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Store (View Only)
    |--------------------------------------------------------------------------
    */
    Route::get('/stores', [\App\Http\Controllers\StoreController::class, 'index']);
    Route::get('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'show']);

    // Coupon Routes (User)
    Route::get('/coupons', [\App\Http\Controllers\CouponController::class, 'index']); // List coupons

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        Route::post('/admin/users/{user}/promote', [\App\Http\Controllers\AdminController::class, 'promote']);

        // Banner
        Route::post('/banners', [\App\Http\Controllers\BannerController::class, 'store']);
        Route::post('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'update']);
        Route::delete('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'destroy']);

        // Store
        Route::post('/stores', [\App\Http\Controllers\StoreController::class, 'store']);
        Route::post('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'update']);
        Route::delete('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'destroy']);

        // Orders
        Route::get('/admin/orders', [\App\Http\Controllers\AdminOrderController::class, 'index']);
        Route::post('/admin/orders/{id}', [\App\Http\Controllers\AdminOrderController::class, 'update']);
        Route::delete('/admin/orders/{id}', [\App\Http\Controllers\AdminOrderController::class, 'destroy']);

        // Coupons
        Route::get('/admin/coupons', [\App\Http\Controllers\CouponController::class, 'index']);
        Route::post('/admin/coupons', [\App\Http\Controllers\CouponController::class, 'store']);
        Route::delete('/admin/coupons/{id}', [\App\Http\Controllers\CouponController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Coupon Check
    |--------------------------------------------------------------------------
    */
    Route::post('/check-coupon', [\App\Http\Controllers\CouponController::class, 'check']);

    // Driver & Delivery Routes
    Route::get('/drivers', [\App\Http\Controllers\DriverController::class, 'index']); // Get all active drivers
    Route::get('/drivers/{id}/location', [\App\Http\Controllers\DriverController::class, 'getLocation']);
    Route::post('/drivers/{id}/location', [\App\Http\Controllers\DriverController::class, 'updateLocation']); // Update location

    // Driver Dashboard Routes (New Request)
    Route::get('/driver/orders', [\App\Http\Controllers\DriverOrderController::class, 'index']);
    Route::post('/driver/orders/{id}/status', [\App\Http\Controllers\DriverOrderController::class, 'updateStatus']);

    // Order Status Update (Driver/User/Admin)
    Route::post('/orders/{id}/update-status', [\App\Http\Controllers\OrderController::class, 'updateStatus']); // Keeping this for backward compatibility or generic use

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Admin Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(['role:admin'])->group(function () {

            Route::post('/admin/users/{user}/promote', [\App\Http\Controllers\AdminController::class, 'promote']);

            // Banner Management
            Route::post('/banners', [\App\Http\Controllers\BannerController::class, 'store']);
            Route::post('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'update']);
            Route::delete('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'destroy']);

            // Store Management
            Route::post('/stores', [\App\Http\Controllers\StoreController::class, 'store']);
            Route::post('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'update']);
            Route::delete('/stores/{id}', [\App\Http\Controllers\StoreController::class, 'destroy']);

            // Order Management
            Route::get('/admin/orders', [\App\Http\Controllers\AdminOrderController::class, 'index']);
            Route::post('/admin/orders/{id}', [\App\Http\Controllers\AdminOrderController::class, 'update']);
            Route::delete('/admin/orders/{id}', [\App\Http\Controllers\AdminOrderController::class, 'destroy']);
            Route::post('/admin/orders/{id}/assign-driver', [\App\Http\Controllers\AdminOrderController::class, 'assignDriver']);

            // Coupon Management
            Route::get('/admin/coupons', [\App\Http\Controllers\CouponController::class, 'index']);
            Route::post('/admin/coupons', [\App\Http\Controllers\CouponController::class, 'store']);
            Route::delete('/admin/coupons/{id}', [\App\Http\Controllers\CouponController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Coupon Check (User)
        |--------------------------------------------------------------------------
        */

    });
});
