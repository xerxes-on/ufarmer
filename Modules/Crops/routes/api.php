<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Crops\Http\Controllers\Api\CropAreaController;
use Modules\Crops\Http\Controllers\Api\CropController;
use Modules\Crops\Http\Controllers\Api\UserCropPriceController;

Route::middleware(['authbridge'])->group(function (): void {

    // Crops routes
    Route::get('crops', [CropController::class, 'index']);
    Route::get('crops/{uuid}', [CropController::class, 'show']);

    // Crop -> Areas bulk attach
    Route::post('crops/{uuid}/areas/multiple', [CropAreaController::class, 'attachMultiple']);

    // User crops routes
    Route::prefix('user/crops')->group(function (): void {
        Route::get('/', [CropController::class, 'userCrops']);
        Route::post('/', [CropController::class, 'attachCrop']);
        Route::post('/multiple', [CropController::class, 'attachMultipleCrops']);
        Route::put('/', [CropController::class, 'syncCrops']);
        Route::delete('/{uuid}', [CropController::class, 'detachCrop']);
    });

    // User crop prices routes
    Route::get('/crops/prices/get', [UserCropPriceController::class, 'userCropsWithWeeklyPrices']);
    Route::get('/crops/{id}/prices', [UserCropPriceController::class, 'userCropPriceHistory']);
});
