<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureContentIngestToken;
use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\Content\ContentDraftController;
use Modules\General\Http\Controllers\Content\ContentSourceController;

Route::middleware([EnsureContentIngestToken::class])->prefix('v1')->group(function (): void {
    Route::get('content-sources', [ContentSourceController::class, 'index'])->name('content-sources.index');
    Route::post('content-drafts', [ContentDraftController::class, 'store'])->name('content-drafts.store');
});
