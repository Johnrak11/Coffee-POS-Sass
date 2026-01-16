<?php

use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Guest API (No Authentication Required)
Route::prefix('guest')->group(function () {
    // QR Scanning & Session
    Route::post('scan/{qrToken}', [GuestController::class, 'scanTable']);

    // Menu
    Route::get('menu/{shopSlug}', [GuestController::class, 'getMenu']);

    // Cart Management
    Route::post('cart/add', [GuestController::class, 'addToCart']);
    Route::get('cart/{sessionToken}', [GuestController::class, 'getCart']);
    Route::patch('cart/{cartItemId}', [GuestController::class, 'updateCartItem']);
    Route::delete('cart/{cartItemId}', [GuestController::class, 'removeCartItem']);

    // Checkout
    Route::post('checkout', [OrderController::class, 'checkout']);

    // Order Status (for payment polling)
    Route::get('order/{orderId}/status', [OrderController::class, 'getOrderStatus']);
});

// KHQR Integration
Route::prefix('khqr')->group(function () {
    Route::post('generate', [\App\Http\Controllers\Api\KhqrController::class, 'generate']);
    Route::post('regenerate', [\App\Http\Controllers\Api\KhqrController::class, 'regenerate']);
    Route::post('check-status', [\App\Http\Controllers\Api\KhqrController::class, 'checkStatus']);
    Route::post('check-status-single', [\App\Http\Controllers\Api\KhqrController::class, 'checkStatusSingle']);
});

// Public Shop Auth (Legacy/Public)
Route::post('shop/verify', [\App\Http\Controllers\Api\ShopAuthController::class, 'verify']);

// Super Admin Auth (Public, but secured)
Route::post('auth/super-admin', [\App\Http\Controllers\Api\SuperAdminAuthController::class, 'login']);

// Super Admin API
Route::prefix('super-admin')->middleware(['auth:sanctum', \App\Http\Middleware\CheckSuperAdmin::class])->group(function () {
    Route::get('stats', [\App\Http\Controllers\Api\SuperAdminController::class, 'getStats']);
    Route::get('shops', [\App\Http\Controllers\Api\SuperAdminController::class, 'getShops']);
    Route::post('shops', [\App\Http\Controllers\Api\SuperAdminController::class, 'store']); // Create Shop
    Route::put('shops/{shopId}', [\App\Http\Controllers\Api\SuperAdminController::class, 'update']); // Edit Shop
    Route::put('shops/{shopId}/reset-password', [\App\Http\Controllers\Api\SuperAdminController::class, 'resetPassword']); // Reset Terminal Password
    Route::post('shops/{shopId}/toggle-status', [\App\Http\Controllers\Api\SuperAdminController::class, 'toggleShopStatus']);
});


// Staff API
Route::prefix('staff')->middleware([\App\Http\Middleware\CheckSubscription::class])->group(function () {
    // ... existing routes
    Route::get('users/{shopSlug}', [\App\Http\Controllers\Api\StaffController::class, 'getStaffList']);
    Route::post('auth', [\App\Http\Controllers\Api\StaffController::class, 'authenticate']);
    Route::post('orders', [\App\Http\Controllers\Api\PosOrderController::class, 'store']);
    Route::get('orders', [\App\Http\Controllers\Api\PosOrderController::class, 'index']); // Order History
    Route::get('orders/{order}', [\App\Http\Controllers\Api\PosOrderController::class, 'show']); // Order Details
    Route::put('orders/{order}/payment-status', [\App\Http\Controllers\Api\PosOrderController::class, 'updatePaymentStatus']); // Update Status

    // Kitchen API
    Route::get('kitchen/{shopSlug}/orders', [\App\Http\Controllers\Api\KitchenController::class, 'index']);
    Route::post('kitchen/orders/{orderId}/status', [\App\Http\Controllers\Api\KitchenController::class, 'updateStatus']);

    // Admin API
    Route::get('admin/{shopSlug}/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
    Route::get('admin/{shopSlug}/transactions', [\App\Http\Controllers\Api\AdminController::class, 'transactions']);

    // Menu Management
    Route::prefix('admin/{shopSlug}/menu')->group(function () {
        Route::get('categories', [\App\Http\Controllers\Api\CategoryManagementController::class, 'index']);
        Route::post('categories', [\App\Http\Controllers\Api\CategoryManagementController::class, 'store']);
        Route::put('categories/{categoryId}', [\App\Http\Controllers\Api\CategoryManagementController::class, 'update']);
        Route::delete('categories/{categoryId}', [\App\Http\Controllers\Api\CategoryManagementController::class, 'destroy']);

        Route::get('products', [\App\Http\Controllers\Api\ProductManagementController::class, 'index']);
        Route::post('products', [\App\Http\Controllers\Api\ProductManagementController::class, 'store']);
        Route::put('products/{productId}', [\App\Http\Controllers\Api\ProductManagementController::class, 'update']);
        Route::delete('products/{productId}', [\App\Http\Controllers\Api\ProductManagementController::class, 'destroy']);

        Route::get('staff', [\App\Http\Controllers\Api\StaffManagementController::class, 'index']);
        Route::post('staff', [\App\Http\Controllers\Api\StaffManagementController::class, 'store']);
        Route::put('staff/{staffId}', [\App\Http\Controllers\Api\StaffManagementController::class, 'update']);
        Route::delete('staff/{staffId}', [\App\Http\Controllers\Api\StaffManagementController::class, 'destroy']);

        Route::get('settings', [\App\Http\Controllers\Api\ShopSettingsController::class, 'show']);
        Route::put('settings', [\App\Http\Controllers\Api\ShopSettingsController::class, 'update']);

        Route::get('tables', [\App\Http\Controllers\Api\TableManagementController::class, 'index']);
        Route::post('tables', [\App\Http\Controllers\Api\TableManagementController::class, 'store']);
        Route::put('tables/{tableId}', [\App\Http\Controllers\Api\TableManagementController::class, 'update']);
        Route::delete('tables/{tableId}', [\App\Http\Controllers\Api\TableManagementController::class, 'destroy']);

        // Option Sets Management (Custom Presets)
        Route::get('option-sets', [\App\Http\Controllers\Api\OptionSetController::class, 'index']);
        Route::post('option-sets', [\App\Http\Controllers\Api\OptionSetController::class, 'store']);
        Route::put('option-sets/{optionSet}', [\App\Http\Controllers\Api\OptionSetController::class, 'update']);
        Route::delete('option-sets/{optionSet}', [\App\Http\Controllers\Api\OptionSetController::class, 'destroy']);
    });
});




