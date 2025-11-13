<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\DocumentsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Manager Performance Dashboard API Routes
| All routes use Supabase for data storage and authentication
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes (require Supabase auth token)
Route::middleware('api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Performance Metrics routes
    Route::prefix('metrics')->group(function () {
        Route::get('/', [MetricsController::class, 'index']);
        Route::get('/statistics', [MetricsController::class, 'statistics']);
        Route::get('/{id}', [MetricsController::class, 'show']);
        Route::post('/', [MetricsController::class, 'store']);
        Route::patch('/{id}', [MetricsController::class, 'update']);
        Route::delete('/{id}', [MetricsController::class, 'destroy']);
    });

    // Documents routes
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentsController::class, 'index']);
        Route::get('/{id}', [DocumentsController::class, 'show']);
        Route::get('/{id}/download', [DocumentsController::class, 'download']);
        Route::post('/upload', [DocumentsController::class, 'upload']);
        Route::patch('/{id}', [DocumentsController::class, 'update']);
        Route::delete('/{id}', [DocumentsController::class, 'destroy']);
    });

    // Legacy Sanctum route
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Manager Performance Dashboard API',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});
