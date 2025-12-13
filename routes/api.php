<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\OwnerController;
use App\Http\Controllers\API\PeternakController;
use App\Http\Controllers\Api\MonitoringAggregateController;
use App\Http\Controllers\Api\ManualAnalysisAggregateController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/guest-report', [AuthController::class, 'guestReport']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);

    // Aggregation endpoints
    Route::get('/monitoring/aggregate', MonitoringAggregateController::class);
    Route::get('/analysis/aggregate', ManualAnalysisAggregateController::class);
    

    // ADMIN ROUTES
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/farms', [AdminController::class, 'getFarms']);
        Route::post('/farms', [AdminController::class, 'createFarm']);
        Route::get('/farms/{id}/config', [AdminController::class, 'getFarmConfig']);
        Route::put('/farms/{id}/config', [AdminController::class, 'updateFarmConfig']);
        Route::post('/farms/{id}/config/reset', [AdminController::class, 'resetFarmConfig']);
        Route::post('/farms/{id}/iot/upload', [AdminController::class, 'uploadIotCsv']);
        Route::get('/requests', [AdminController::class, 'getRequests']);
        Route::put('/requests/{id}/status', [AdminController::class, 'updateRequestStatus']);
        Route::get('/owners', [AdminController::class, 'getOwners']);
        Route::get('/owners/{owner_id}/farms', [AdminController::class, 'getFarmsByOwner']);
        Route::get('/peternaks/{owner_id}', [AdminController::class, 'getPeternaks']);
        Route::post('/broadcast', [AdminController::class, 'broadcastToOwners']);
        Route::post('/farms/{id}/alert', [AdminController::class, 'sendFarmAlert']);
    });

    // OWNER ROUTES
    Route::prefix('owner')->group(function () {
        Route::get('/dashboard', [OwnerController::class, 'dashboard']);
        Route::get('/export/{farm_id}', [OwnerController::class, 'export']);
        Route::post('/requests', [OwnerController::class, 'submitRequest']);
        // REMOVED: /monitoring/{farm_id} and /analytics/{farm_id}
        // Use aggregate endpoints instead: /monitoring/aggregate and /analysis/aggregate
    });

    // PETERNAK ROUTES
    Route::prefix('peternak')->group(function () {
        Route::get('/dashboard', [PeternakController::class, 'dashboard']);
        Route::post('/reports', [PeternakController::class, 'submitReport']);
        Route::get('/profile', [PeternakController::class, 'getProfile']);
        Route::put('/profile', [PeternakController::class, 'updateProfile']);
        Route::post('/profile/photo', [PeternakController::class, 'uploadPhoto']);
        Route::post('/otp/send', [PeternakController::class, 'sendOtp']);
        Route::post('/otp/verify', [PeternakController::class, 'verifyOtp']);
    });
});
