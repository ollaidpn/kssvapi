<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;

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

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
});
