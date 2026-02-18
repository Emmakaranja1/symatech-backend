<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;



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

        // product management
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::get('/admin/products/{id}', [ProductController::class, 'show']);
        Route::put('/admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/admin/products', [ProductController::class, 'index']);

        // order management
        Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
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
    });
    
});
