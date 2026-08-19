<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Weather\Http\Controllers\WeatherController;

Route::prefix('weather')->group(function (): void {
    Route::get('hourly', [WeatherController::class, 'hourly'])->name('weather.hourly');
    Route::get('daily', [WeatherController::class, 'daily'])->name('weather.daily');
    Route::get('weekly', [WeatherController::class, 'weekly'])->name('weather.weekly');
    Route::get('crop-alert', [WeatherController::class, 'cropAlert'])->name('weather.crop-alert');
    Route::get('crop-forecast', [WeatherController::class, 'cropForecast'])->name('weather.crop-forecast');
});
