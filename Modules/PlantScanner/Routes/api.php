<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PlantScanner\Http\Controllers\PlantScannerController;

Route::middleware(['authbridge'])->prefix('plant-scanner')->group(function (): void {
    Route::post('/scan', [PlantScannerController::class, 'scan']);
    Route::get('/scans', [PlantScannerController::class, 'index']);
    Route::get('/scans/{id}', [PlantScannerController::class, 'show']);
});
