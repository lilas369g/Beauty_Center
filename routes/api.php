<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public: the only route allowed without auth in this module.
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('service-categories/options', [ServiceCategoryController::class, 'options']);
        Route::get('service-categories', [ServiceCategoryController::class, 'index']);
        Route::post('service-categories', [ServiceCategoryController::class, 'store']);
        Route::get('service-categories/{serviceCategory:uuid}', [ServiceCategoryController::class, 'show']);
        Route::patch('service-categories/{serviceCategory:uuid}', [ServiceCategoryController::class, 'update']);
        Route::patch('service-categories/{serviceCategory:uuid}/status', [ServiceCategoryController::class, 'updateStatus']);
        Route::delete('service-categories/{serviceCategory:uuid}', [ServiceCategoryController::class, 'destroy']);
        Route::get('services', [ServiceController::class, 'index']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::get('services/{service:uuid}', [ServiceController::class, 'show']);
        Route::patch('services/{service:uuid}', [ServiceController::class, 'update']);
        Route::patch('services/{service:uuid}/status', [ServiceController::class, 'updateStatus']);
    });
});
