<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\AreaController;
use Modules\Core\Http\Controllers\Api\AreaCropController;
use Modules\Core\Http\Controllers\Api\PaidServiceController;
use Modules\Core\Http\Controllers\Api\RegionController;
use Modules\Core\Http\Controllers\Api\UserDetailController;

Route::middleware(['authbridge'])->group(function (): void {

    // Paid Services management routes
    Route::prefix('paid-services')->group(function (): void {
        Route::get('/', [PaidServiceController::class, 'index']);
        Route::post('/', [PaidServiceController::class, 'store']);
        Route::get('/{paidService}', [PaidServiceController::class, 'show']);
        Route::put('/{paidService}', [PaidServiceController::class, 'update']);
        Route::delete('/{paidService}', [PaidServiceController::class, 'destroy']);
        Route::post('/{paidService}/restore', [PaidServiceController::class, 'restore'])->withTrashed();
        Route::post('/{paidService}/toggle-status', [PaidServiceController::class, 'toggleStatus']);
    });

    // Regions routes
    Route::get('regions', [RegionController::class, 'index']);
    Route::get('regions/{region}', [RegionController::class, 'show']);
    Route::get('regions/{region}/cities', [RegionController::class, 'cities']);

    // User details routes
    Route::prefix('user-details')->group(function (): void {
        Route::get('/', [UserDetailController::class, 'show']);
        Route::post('/', [UserDetailController::class, 'store']);
        Route::post('/update', [UserDetailController::class, 'update']);
        Route::delete('/', [UserDetailController::class, 'destroy']);
        Route::delete('/image', [UserDetailController::class, 'deleteImage']);
    });

    // Nearby users route
    Route::get('users/nearby', [UserDetailController::class, 'nearby']);

    // Alternative routes for managing other users (admin functionality)
    Route::prefix('users/{user}/details')->group(function (): void {
        Route::get('/', [UserDetailController::class, 'show']);
        Route::post('/', [UserDetailController::class, 'store']);
        Route::post('/update', [UserDetailController::class, 'update']);
        Route::delete('/', [UserDetailController::class, 'destroy']);
        Route::delete('/image', [UserDetailController::class, 'deleteImage']);
    });

    // Area routes
    Route::apiResource('areas', AreaController::class);
    Route::post('areas/calculate', [AreaController::class, 'calculateArea']);

    // Area crops routes
    Route::get('areas/{area}/crops', [AreaCropController::class, 'index']);
    Route::post('areas/{area}/crops', [AreaCropController::class, 'store']);
    Route::post('areas/{area}/crops/multiple', [AreaCropController::class, 'attachMultiple']);
    Route::put('areas/{area}/crops/{uuid}', [AreaCropController::class, 'update']);
    Route::patch('areas/{area}/crops/{uuid}/toggle-active', [AreaCropController::class, 'toggleActive']);
    Route::post('areas/{area}/crops/{uuid}/harvest', [AreaCropController::class, 'harvest']);
    Route::delete('areas/{area}/crops/{uuid}', [AreaCropController::class, 'destroy']);
});
