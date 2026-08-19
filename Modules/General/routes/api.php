<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\ArticleController;
use Modules\General\Http\Controllers\ProductStatController;
use Modules\General\Http\Controllers\StoryController;

Route::middleware(['authbridge'])->prefix('v1')->group(function (): void {
    Route::get('articles/for-farmer', [ArticleController::class, 'forFarmer'])->name('articles.for-farmer');
    Route::get('crops/{id}/stats', [ProductStatController::class, 'getCropStats'])->name('crops.stats');
    Route::get('product-stats/today', [ProductStatController::class, 'today'])->name('product-stats.today');
    Route::get('stories', [StoryController::class, 'index'])->name('stories.index');
    Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
});
