<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ProxyController;
use App\Http\Controllers\Api\LocalController;

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
// Proxy Routes for HomeIP API (Public - Avoids CORS)
// ============================================

Route::prefix('proxy')->group(function () {
    Route::get('/products', [ProxyController::class, 'getProducts']);
    Route::get('/products/search/{query}', [ProxyController::class, 'searchProducts']);
    Route::get('/products/{id}', [ProxyController::class, 'getProduct']);
    Route::get('/categories', [ProxyController::class, 'getCategories']);
    Route::get('/categories/{id}', [ProxyController::class, 'getCategory']);
});

// ============================================
// Local Data Routes (Public - Fast Local DB)
// ============================================

Route::prefix('local')->group(function () {
    // App Info (public for contact page)
    Route::get('/app-info', [LocalController::class, 'getAppInfo']);
    
    // Categories
    Route::get('/categories', [LocalController::class, 'getCategories']);
    Route::get('/categories/top', [LocalController::class, 'getTopCategories']);
    Route::get('/categories/{id}', [LocalController::class, 'getCategory']);
    
    // Products
    Route::get('/products', [LocalController::class, 'getProducts']);
    Route::get('/products/recent', [LocalController::class, 'getRecentProducts']);
    Route::get('/products/random', [LocalController::class, 'getRandomProducts']);
    Route::get('/products/search/{query}', [LocalController::class, 'searchProducts']);
    Route::get('/products/category/{categoryId}', [LocalController::class, 'getProductsByCategory']);
    Route::get('/products/{id}', [LocalController::class, 'getProduct']);
    Route::get('/products/{id}/related', [LocalController::class, 'getRelatedProducts']);
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
// Webhook Routes (Public - No Auth Required)
// ============================================

Route::prefix('webhook')->group(function () {
    Route::post('/fayko', [WebhookController::class, 'fayko']);
});

// ============================================
// Public Order Route (For Payment Success Page)
// ============================================

Route::get('/order/{paymentId}', [AccountController::class, 'getOrderByPayment']);

// ============================================
// Protected Routes (Requires Sanctum Token)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/check', [AuthController::class, 'checkToken']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    // Account Dashboard & Orders & Payments
    Route::get('/account/dashboard', [AccountController::class, 'dashboard']);
    Route::get('/account/orders', [AccountController::class, 'orders']);
    Route::get('/account/orders/{id}', [AccountController::class, 'orderDetail']);
    Route::get('/account/payments', [AccountController::class, 'payments']);
    Route::post('/account/avatar', [AccountController::class, 'updateAvatar']);

    // Orders - Create, Check, Cancel
    Route::post('/orders', [AccountController::class, 'createOrder']);
    Route::post('/orders/check', [AccountController::class, 'checkOrderStatus']);
    Route::post('/orders/cancel', [AccountController::class, 'cancelOrder']);

    // Promo Codes - Validate
    Route::post('/promo-codes/validate', [AccountController::class, 'validatePromoCode']);

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
    
    // Payments Management
    Route::get('/payments', [AdminController::class, 'getPayments']);
    
    // Promo Codes Management
    Route::get('/promo-codes', [AdminController::class, 'getPromoCodes']);
    Route::post('/promo-codes', [AdminController::class, 'createPromoCode']);
    Route::put('/promo-codes/{id}', [AdminController::class, 'updatePromoCode']);
    Route::delete('/promo-codes/{id}', [AdminController::class, 'deletePromoCode']);
    
    // Categories Management
    Route::get('/categories', [AdminController::class, 'getCategories']);
    Route::post('/categories', [AdminController::class, 'createCategory']);
    Route::put('/categories/{id}', [AdminController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [AdminController::class, 'deleteCategory']);
    
    // Brands Management
    Route::get('/brands', [AdminController::class, 'getBrands']);
    Route::post('/brands', [AdminController::class, 'createBrand']);
    Route::put('/brands/{id}', [AdminController::class, 'updateBrand']);
    Route::delete('/brands/{id}', [AdminController::class, 'deleteBrand']);
    
    // Items (Inventory) Management
    Route::get('/items', [AdminController::class, 'getItems']);
    Route::post('/items', [AdminController::class, 'createItem']);
    Route::put('/items/{id}', [AdminController::class, 'updateItem']);
    Route::delete('/items/{id}', [AdminController::class, 'deleteItem']);
    
    // Sections Management
    Route::get('/sections', [AdminController::class, 'getSections']);
    Route::post('/sections', [AdminController::class, 'createSection']);
    Route::post('/sections/{id}', [AdminController::class, 'updateSection']);
    Route::delete('/sections/{id}', [AdminController::class, 'deleteSection']);
    
    // AppInfo Management
    Route::get('/app-info', [AdminController::class, 'getAppInfo']);
    Route::post('/app-info', [AdminController::class, 'updateAppInfo']);
    Route::post('/app-info/toggle-image-filter', [AdminController::class, 'toggleImageFilter']);
    
    // Addresses Management (JSON in app_infos.addresses)
    Route::post('/app-info/addresses', [AdminController::class, 'addAddress']);
    Route::put('/app-info/addresses/{addressId}', [AdminController::class, 'updateAddress']);
    Route::delete('/app-info/addresses/{addressId}', [AdminController::class, 'deleteAddress']);
    Route::put('/app-info/addresses/{addressId}/default', [AdminController::class, 'setDefaultAddress']);
    
    // Synchronization Management
    Route::get('/synchronizations', [AdminController::class, 'getSynchronizations']);
    Route::post('/synchronizations/run', [AdminController::class, 'runSync']);
    Route::post('/synchronizations/apply', [AdminController::class, 'applySyncToLocal']);
    Route::post('/synchronizations/mark-synced', [AdminController::class, 'markAsSynced']);
    Route::post('/synchronizations/migrate/{id}', [AdminController::class, 'migrateSingleEntry']);
    Route::get('/synchronizations/count-pending', [AdminController::class, 'countPendingMigrations']);
    Route::delete('/synchronizations', [AdminController::class, 'deleteSyncEntries']);
    Route::delete('/synchronizations/{id}', [AdminController::class, 'deleteSyncEntry']);
    
    // Admin Users Management
    Route::get('/users', [AdminController::class, 'getAdmins']);
    Route::post('/users', [AdminController::class, 'createAdmin']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteAdmin']);
    Route::post('/users/{id}/resend-invitation', [AdminController::class, 'resendAdminInvitation']);
    Route::put('/users/{id}/toggle-status', [AdminController::class, 'toggleAdminStatus']);
});

// ============================================
// Public Admin Routes (No Auth Required)
// ============================================

Route::post('/admin/activate', [AdminController::class, 'activateAdmin']);
