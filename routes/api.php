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

// Public routes with standard API rate limiting
Route::middleware('throttle:api')->group(function () {
    Route::apiResource('products', ProductController::class)->only(['index','show']);
    Route::apiResource('categories', CategoryController::class)->only(['index','show']);
    Route::get('categories/{category}/products', [ProductController::class, 'products'])->name('categories.products');
});

// Protected product management routes
Route::middleware(['auth:sanctum', 'permission:create products', 'throttle:api'])->group(function () {
    Route::apiResource('products', ProductController::class)->except(['index','show']);
});

// Protected category management routes
Route::middleware(['auth:sanctum', 'permission:create categories', 'throttle:api'])->group(function () {
    Route::apiResource('categories', CategoryController::class)->except(['index','show']);
});

// Cart routes with standard rate limiting
Route::middleware(['auth:sanctum', 'permission:create orders', 'throttle:api'])->group(function () {
    Route::apiResource('carts', CartController::class)->except(['show']);
});

// Checkout and order routes with standard rate limiting
Route::middleware(['auth:sanctum', 'permission:create orders', 'throttle:api'])->group(function () {
    Route::post('checkout', [CheckoutController::class, 'checkout']);
    Route::get('order', [CheckoutController::class, 'orderHistory']);
    Route::get('order/{order}', [CheckoutController::class, 'orderDetails']);
});

// Payment routes with stricter rate limiting
Route::middleware(['auth:sanctum', 'permission:create orders', 'throttle:payment'])->group(function () {
    Route::post('order/{order}/payments', [PaymentController::class, 'createPayment']);
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirmPayment']);
});

// Stripe webhook - no auth but with basic rate limiting
Route::post('webhooks/stripe', [PaymentController::class, 'stripeWebhook'])->middleware('throttle:60,1');

// Include auth routes (they will use 'auth' rate limiter)
include_once __DIR__.'/auth.php';