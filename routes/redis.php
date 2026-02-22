<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Redis\ShoppingCartController;
use App\Http\Controllers\Redis\UserPreferencesController;
use App\Http\Controllers\Redis\PaymentSessionController;
use App\Http\Controllers\Redis\RateLimitController;
use App\Http\Controllers\Redis\RedisConnectionController;

/*
|--------------------------------------------------------------------------
| Redis API Routes (Public for Testing)
|--------------------------------------------------------------------------
|
| These routes are separated from main API routes to avoid middleware conflicts.
| All Redis shopping cart and state management routes are defined here.
|
*/

Route::prefix('redis')->group(function () {
    // Shopping Cart Routes
    Route::post('/cart/add', [ShoppingCartController::class, 'addItem']);
    Route::get('/cart', [ShoppingCartController::class, 'getCart']);
    Route::delete('/cart/item', [ShoppingCartController::class, 'removeItem']);
    Route::put('/cart/quantity', [ShoppingCartController::class, 'updateQuantity']);
    Route::delete('/cart', [ShoppingCartController::class, 'clearCart']);
    Route::get('/cart/summary', [ShoppingCartController::class, 'getCartSummary']);

    // User Preferences Routes
    Route::post('/preferences/set', [UserPreferencesController::class, 'setPreference']);
    Route::get('/preferences/get', [UserPreferencesController::class, 'getPreference']);
    Route::get('/preferences/all', [UserPreferencesController::class, 'getAllPreferences']);
    Route::delete('/preferences/remove', [UserPreferencesController::class, 'removePreference']);
    Route::delete('/preferences/clear', [UserPreferencesController::class, 'clearAllPreferences']);
    Route::post('/preferences/multiple', [UserPreferencesController::class, 'setMultiplePreferences']);

    // Payment Session Routes
    Route::post('/payment/session/create', [PaymentSessionController::class, 'createSession']);
    Route::get('/payment/session', [PaymentSessionController::class, 'getSession']);
    Route::put('/payment/session/update', [PaymentSessionController::class, 'updateSession']);
    Route::delete('/payment/session', [PaymentSessionController::class, 'deleteSession']);
    Route::put('/payment/session/extend', [PaymentSessionController::class, 'extendSession']);
    Route::get('/payment/session/validity', [PaymentSessionController::class, 'checkSessionValidity']);

    // Rate Limiting Routes
    Route::post('/rate-limit/check', [RateLimitController::class, 'checkRateLimit']);
    Route::delete('/rate-limit/clear', [RateLimitController::class, 'clearRateLimit']);
    Route::get('/rate-limit/status', [RateLimitController::class, 'getRateLimitStatus']);
    Route::get('/rate-limit/test', [RateLimitController::class, 'testRateLimiting']);
    Route::get('/rate-limit/blocked', [RateLimitController::class, 'isBlocked']);

    // Redis Connection Routes
    Route::get('/connection/test', [RedisConnectionController::class, 'testConnection']);
    Route::get('/connection/operations', [RedisConnectionController::class, 'testBasicOperations']);
    Route::get('/connection/hash', [RedisConnectionController::class, 'testHashOperations']);
    Route::get('/connection/info', [RedisConnectionController::class, 'getRedisInfo']);
    Route::get('/connection/monitor', [RedisConnectionController::class, 'monitorConnection']);
    Route::get('/connection/health', [RedisConnectionController::class, 'healthCheck']);
});
