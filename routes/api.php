<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\JWTAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\DatabaseResetController;
use App\Http\Controllers\CartController;

// Health check endpoint for deployment monitoring
Route::get('/health', [HealthController::class, 'index']);

// Temporary seeder endpoint for production admin setup
Route::get('/seed-admin', function () {
    try {
        Artisan::call('db:seed', ['--class' => 'ProductionAdminSeeder']);
        return response()->json(['message' => 'Admin seeder executed successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Database reset endpoint for clearing and reseeding
Route::get('/database-reset', [DatabaseResetController::class, 'clearAndReseed']);

// Public routes
Route::post('/register', [JWTAuthController::class, 'register']);
Route::post('/login', [JWTAuthController::class, 'login']);

// Public products endpoint
Route::get('/products', [ProductController::class, 'publicIndex']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Stock check endpoint (public for frontend validation)
Route::post('/stock/check', [OrderController::class, 'checkStock']);

// Redis cart endpoints (protected)
Route::middleware('jwt.auth')->group(function () {
    Route::post('/redis/cart/add', [CartController::class, 'addToCart']);
    Route::put('/redis/cart/quantity', [CartController::class, 'updateCartItemQuantity']);
    Route::delete('/redis/cart/item', [CartController::class, 'removeFromCart']);
    Route::delete('/redis/cart', [CartController::class, 'clearCart']);
    Route::get('/redis/cart', [CartController::class, 'getCart']);
    Route::get('/redis/cart/count', [CartController::class, 'getCartCount']);
});

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
        Route::get('/admin/products', [ProductController::class, 'adminIndex']);

        // order management
        Route::get('/admin/orders', [OrderController::class, 'adminIndex']); // List All Orders with filters and pagination
        Route::get('/admin/orders/export/excel', [OrderController::class, 'exportExcel']); // Export Orders to Excel
        Route::get('/admin/orders/export/pdf', [OrderController::class, 'exportPdf']); // Export Orders to PDF
        Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']); // Update order status

        // Payment management
        Route::get('/admin/payments', [PaymentController::class, 'getAdminPayments']);
        Route::post('/payments/refund', [PaymentController::class, 'processRefund']);

        // Reporting System (Admin Only)
        Route::prefix('admin/reports')->group(function () {
            // Real-time Dashboard Data
            Route::get('/realtime-data', [ReportingController::class, 'realtimeData']);
            
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
        // order management
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']); 
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::get('/orders/{id}/payment-status', [OrderController::class, 'getPaymentStatus']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

        // Payment management
        Route::post('/payments/mpesa/initiate', [PaymentController::class, 'initiateMpesaPayment']);
        Route::post('/payments/flutterwave/initiate', [PaymentController::class, 'initiateFlutterwavePayment']);
        Route::post('/payments/mpesa/verify', [PaymentController::class, 'verifyMpesaPayment']);
        Route::post('/payments/flutterwave/verify', [PaymentController::class, 'verifyFlutterwavePayment']);
        Route::get('/payments', [PaymentController::class, 'getUserPayments']);
    });
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
