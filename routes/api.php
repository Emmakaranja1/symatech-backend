<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ------------------- ADMIN ROUTES -------------------
    Route::middleware('role:admin')->group(function () {
        // User management
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::patch('/admin/users/{id}/activate', [UserController::class, 'activate']);
        Route::patch('/admin/users/{id}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/admin/register', [AuthController::class, 'registerAdmin']);

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

    // Debug route for authentication testing
    Route::get('/debug-auth', function () {
        if (auth()->check()) {
            return response()->json([
                'authenticated' => true,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'user_role' => auth()->user()->role,
            ]);
        } else {
            return response()->json([
                'authenticated' => false,
                'message' => 'No authenticated user found'
            ]);
        }
    })->middleware('auth:sanctum');

    // Debug route for M-PESA configuration
    Route::get('/debug-mpesa-config', function () {
        return response()->json([
            'mpesa_config' => [
                'consumer_key' => config('services.mpesa.consumer_key') ? 'SET' : 'NOT SET',
                'consumer_secret' => config('services.mpesa.consumer_secret') ? 'SET' : 'NOT SET',
                'passkey' => config('services.mpesa.passkey') ? 'SET' : 'NOT SET',
                'shortcode' => config('services.mpesa.shortcode'),
                'environment' => config('services.mpesa.environment'),
                'callback_url' => config('services.mpesa.callback_url'),
            ],
            'app_url' => config('app.url'),
            'solutions' => [
                '1. Set MPESA_CALLBACK_URL in .env to a public URL',
                '2. Use ngrok for local development: https://ngrok.com/download',
                '3. Example: MPESA_CALLBACK_URL=https://abc123.ngrok.io/api/callbacks/mpesa'
            ]
        ]);
    });

    Route::get('/test-activity', function() {
        \Log::info('Testing direct activity log');
        activity()->log('Test activity log');
        return response()->json(['message' => 'Test activity logged']);
    });
});

// Payment callback routes (public, but secured by validation)
Route::post('/callbacks/mpesa', [PaymentController::class, 'handleMpesaCallback']);
Route::post('/callbacks/flutterwave', [PaymentController::class, 'handleFlutterwaveCallback']);
