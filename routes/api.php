<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ============================================
// Sections Routes (Public)
// ============================================

Route::prefix('sections')->group(function () {
    Route::get('/', [SectionController::class, 'getByType']);
    Route::get('/hero', [SectionController::class, 'getHeroSection']);
    Route::get('/ads', [SectionController::class, 'getAdsSection']);
});

// ============================================
// Authentication Routes (Public)
// ============================================

Route::prefix('auth')->group(function () {
    // Login
    Route::post('/login', [AuthController::class, 'login']);

    // Registration (3 steps)
    Route::post('/register/send-otp', [AuthController::class, 'registerSendOtp']);
    Route::post('/register/verify-otp', [AuthController::class, 'registerVerifyOtp']);
    Route::post('/register/complete', [AuthController::class, 'registerComplete']);

    // Password Reset (3 steps)
    Route::post('/forgot-password/send-otp', [AuthController::class, 'forgotPasswordSendOtp']);
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'forgotPasswordVerifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Resend OTP
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
});

// ============================================
// Protected Routes (Requires Sanctum Token)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    // Account Dashboard
    Route::get('/account/dashboard', [AccountController::class, 'dashboard']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
});

// ============================================
// Admin Routes (Requires Sanctum Token + Admin)
// ============================================

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    
    // Clients Management
    Route::get('/clients', [AdminController::class, 'clients']);
    Route::get('/clients/{id}', [AdminController::class, 'clientDetail']);
    
    // Orders Management
    Route::get('/orders', [AdminController::class, 'getOrders']);
    Route::get('/orders/{id}', [AdminController::class, 'getOrderDetail']);
    Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);
    
    // Sections Management
    Route::get('/sections', [AdminController::class, 'getSections']);
    Route::post('/sections', [AdminController::class, 'createSection']);
    Route::post('/sections/{id}', [AdminController::class, 'updateSection']);
    Route::delete('/sections/{id}', [AdminController::class, 'deleteSection']);
    
    // AppInfo Management
    Route::get('/app-info', [AdminController::class, 'getAppInfo']);
    Route::post('/app-info', [AdminController::class, 'updateAppInfo']);
});
