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

      // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::patch('/admin/users/{id}/activate', [UserController::class, 'activate']);
        Route::patch('/admin/users/{id}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::put('/admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/admin/orders', [OrderController::class, 'index']);
    });

    // Normal user routes
    Route::middleware('role:user')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
    });
    
});
