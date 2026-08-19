<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AgroPrices\Http\Controllers\PriceController;

Route::prefix('v1')->group(function (): void {
    Route::get('prices/today', [PriceController::class, 'today'])->name('prices.today');
});
