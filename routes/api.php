<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Authentication Routes (No middleware)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password/reset-link', [AuthController::class, 'sendPasswordResetLink']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
});

// Protected Authentication Routes (Require authentication)
Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);
    Route::get('/check', [AuthController::class, 'checkAuth']);

    // Profile management
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/password/change', [AuthController::class, 'changePassword']);

    // Email verification
    Route::post('/email/resend', [AuthController::class, 'resendEmailVerification']);
});

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is healthy',
        'api_version' => config('api.version', '1.0.0'),
        'timestamp' => now()->toISOString(),
    ]);
});

// Public Company Routes (No authentication required)
Route::prefix('companies')->group(function () {
    Route::get('/', [CompanyController::class, 'index']); // Get all companies
    Route::get('/active', [CompanyController::class, 'active']); // Get active companies
    Route::get('/vip', [CompanyController::class, 'vip']); // Get VIP companies
    Route::get('/statistics', [CompanyController::class, 'statistics']); // Get statistics
    Route::get('/{id}', [CompanyController::class, 'show'])->where('id', '[0-9]+'); // Get company by ID
});

// Protected Company Routes (Require authentication)
Route::middleware(['auth:sanctum'])->prefix('companies')->group(function () {
    Route::post('/', [CompanyController::class, 'store']); // Create company
    Route::patch('/{id}', [CompanyController::class, 'update'])->where('id', '[0-9]+'); // Update company
    Route::delete('/{id}', [CompanyController::class, 'destroy'])->where('id', '[0-9]+'); // Delete company
    Route::get('/my', [CompanyController::class, 'myCompanies']); // Get current user's companies
});

// Public Listing Routes (No authentication required)
Route::prefix('listings')->group(function () {
    Route::get('/', [ListingController::class, 'index']); // Get all listings
    Route::get('/active', [ListingController::class, 'active']); // Get active listings
    Route::get('/featured', [ListingController::class, 'featured']); // Get featured listings
    Route::get('/type/{type}', [ListingController::class, 'byType']); // Get listings by type
    Route::get('/search', [ListingController::class, 'search']); // Search listings
    Route::get('/statistics', [ListingController::class, 'statistics']); // Get statistics
    Route::get('/{id}', [ListingController::class, 'show'])->where('id', '[0-9]+'); // Get listing by ID
    Route::get('/slug/{slug}', [ListingController::class, 'showBySlug']); // Get listing by slug
});

// Protected Listing Routes (Require authentication)
Route::middleware(['auth:sanctum'])->prefix('listings')->group(function () {
    Route::post('/', [ListingController::class, 'store']); // Create listing
    Route::patch('/{id}', [ListingController::class, 'update'])->where('id', '[0-9]+'); // Update listing
    Route::delete('/{id}', [ListingController::class, 'destroy'])->where('id', '[0-9]+'); // Delete listing
    Route::get('/my', [ListingController::class, 'myListings']); // Get current user's listings
    Route::patch('/{id}/toggle-status', [ListingController::class, 'toggleStatus'])->where('id', '[0-9]+'); // Toggle listing status
});
