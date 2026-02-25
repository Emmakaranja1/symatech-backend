<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\JWTAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Redis\ShoppingCartController;
use App\Http\Controllers\Redis\UserPreferencesController;
use App\Http\Controllers\Redis\PaymentSessionController;
use App\Http\Controllers\Redis\RateLimitController;
use App\Http\Controllers\Redis\RedisConnectionController;

// Health check endpoint for deployment monitoring
Route::get('/health', [HealthController::class, 'index']);

// Public routes
Route::post('/register', [JWTAuthController::class, 'register']);
Route::post('/login', [JWTAuthController::class, 'login']);

// JWT Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [JWTAuthController::class, 'register']);
    Route::post('/login', [JWTAuthController::class, 'login']);
    
    // JWT protected routes
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/me', [JWTAuthController::class, 'me']);
        Route::post('/logout', [JWTAuthController::class, 'logout']);
        Route::post('/refresh', [JWTAuthController::class, 'refresh']);
        Route::post('/change-password', [JWTAuthController::class, 'changePassword']);
    });
});

// Protected routes
Route::middleware('jwt.auth')->group(function () {
    // ------------------- ADMIN ROUTES -------------------
    Route::middleware('role:admin')->group(function () {
        // User management
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::patch('/admin/users/{id}/activate', [UserController::class, 'activate']);
        Route::patch('/admin/users/{id}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/admin/register', [JWTAuthController::class, 'registerAdmin']);

        // product management
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::get('/admin/products/{id}', [ProductController::class, 'show']);
        Route::put('/admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/admin/products', [ProductController::class, 'index']);

        // order management
        Route::get('/admin/orders', [OrderController::class, 'adminIndex']); // List All Orders with filters and pagination
        Route::get('/admin/orders/export/excel', [OrderController::class, 'exportExcel']); // Export Orders to Excel
        Route::get('/admin/orders/export/pdf', [OrderController::class, 'exportPdf']); // Export Orders to PDF

        // Payment management
        Route::get('/admin/payments', [PaymentController::class, 'getAdminPayments']);
        Route::post('/payments/refund', [PaymentController::class, 'processRefund']);

        // Reporting System (Admin Only)
        Route::prefix('admin/reports')->group(function () {
            // Dashboard Statistics
            Route::get('/dashboard', [ReportingController::class, 'dashboardStats']);
            
            // User Registration Trends
            Route::get('/user-registration-trends', [ReportingController::class, 'userRegistrationTrends']);
            Route::get('/user-registration-trends/export/excel', [ReportingController::class, 'exportUserRegistrationTrendsExcel']);
            Route::get('/user-registration-trends/export/pdf', [ReportingController::class, 'exportUserRegistrationTrendsPdf']);
            
            // Activity Log Reports (All Users)
            Route::get('/activity-log', [ReportingController::class, 'activityLogReport']);
            Route::get('/activity-log/export/excel', [ReportingController::class, 'exportActivityLogExcel']);
            Route::get('/activity-log/export/pdf', [ReportingController::class, 'exportActivityLogPdf']);
            
            // Normal User Activity Reports (Normal Users Only)
            Route::get('/normal-user-activity', [ReportingController::class, 'normalUserActivityLog']);
            Route::get('/normal-user-activity/export/excel', [ReportingController::class, 'exportNormalUserActivityExcel']);
            Route::get('/normal-user-activity/export/pdf', [ReportingController::class, 'exportNormalUserActivityPdf']);
        });
    });

    // ------------------- NORMAL USER ROUTES -------------------
    Route::middleware('role:user')->group(function () {
        // product management
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        
        // order management
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']); 
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

        // Payment management
        Route::post('/payments/mpesa/initiate', [PaymentController::class, 'initiateMpesaPayment']);
        Route::post('/payments/flutterwave/initiate', [PaymentController::class, 'initiateFlutterwavePayment']);
        Route::post('/payments/mpesa/verify', [PaymentController::class, 'verifyMpesaPayment']);
        Route::post('/payments/flutterwave/verify', [PaymentController::class, 'verifyFlutterwavePayment']);
        Route::get('/payments', [PaymentController::class, 'getUserPayments']);
    });

// Test public route
Route::get('/test-public', function () {
    return response()->json([
        'success' => true,
        'message' => 'Public route working',
        'timestamp' => now()
    ]);
});

// Payment callback routes (public, but secured by validation)
Route::post('/callbacks/mpesa', [PaymentController::class, 'handleMpesaCallback']);
Route::post('/callbacks/flutterwave', [PaymentController::class, 'handleFlutterwaveCallback']);

// Redis State Management Routes (Public for testing)
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
});
