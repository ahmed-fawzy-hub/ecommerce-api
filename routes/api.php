<?php

use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Auth\CustomerAuthController;
use App\Http\Controllers\Api\Auth\DeliveryAuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
//     Route::get('/me', [AuthController::class, 'me']);
//     Route::get('/token', [AuthController::class, 'getAccessToken']);
// });

// Route::prefix('admin')->group(function () {
//     Route::post('/register', [AdminAuthController::class, 'register']);
//     Route::post('/login', [AdminAuthController::class, 'login']);
//     Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
//         Route::post('/logout', [AdminAuthController::class, 'logout']);
//         Route::get('/me', [AdminAuthController::class, 'me']);
//         Route::get('/token', [AdminAuthController::class, 'getAccessToken']);
//     });
// });
// Route::prefix('customer')->group(function () {
//     Route::post('/register', [CustomerAuthController::class, 'register']);
//     Route::post('/login', [CustomerAuthController::class, 'login']);
//     Route::middleware(['auth:sanctum', 'isCustomer'])->group(function () {
//         Route::post('/logout', [CustomerAuthController::class, 'logout']);
//         Route::get('/me', [CustomerAuthController::class, 'me']);
//         Route::get('/token', [CustomerAuthController::class, 'getAccessToken']);
//     });
// });
// Route::prefix('delivery')->group(function () {
//     Route::post('/register', [DeliveryAuthController::class, 'register']);
//     Route::post('/login', [DeliveryAuthController::class, 'login']);
//     Route::middleware(['auth:sanctum', 'isDelivery'])->group(function () {
//         Route::post('/logout', [DeliveryAuthController::class, 'logout']);
//         Route::get('/me', [DeliveryAuthController::class, 'me']);
//         Route::get('/token', [DeliveryAuthController::class, 'getAccessToken']);
//     });
// });
Route::apiResource('products', ProductController::class)->only([
    'index','show']);
Route::middleware('auth:sanctum', 'permission:create products')->group(function () {
    Route::apiResource('products', ProductController::class)->except([
        'index','show']);
});
Route::apiResource('categories', CategoryController::class)->only([
    'index','show']);
Route::middleware('auth:sanctum', 'permission:create categories')->group(function () {
    Route::apiResource('categories', CategoryController::class)->except([
        'index','show']);
});
Route::middleware('auth:sanctum', 'permission:create orders')->group(function () {
    Route::apiResource('carts', CartController::class)->except([
        'show']);
});
Route::middleware('auth:sanctum', 'permission:create orders')->group(function () {
    Route::post('checkout', [CheckoutController::class, 'checkout']);
    Route::get('order', [CheckoutController::class, 'orderHistory']);
    Route::get('order/{order}', [CheckoutController::class, 'orderDetails']);
    Route::post('order/{order}/payments', [PaymentController::class, 'createPayment']);
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirmPayment']);
});
Route::post('webhooks/stripe', [PaymentController::class, 'stripeWebhook']);
Route::get('categories/{category}/products', [ProductController::class, 'products'])->name('categories.products');

include_once __DIR__.'/auth.php';