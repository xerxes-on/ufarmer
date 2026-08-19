<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\JobsServices\Http\Controllers\JobsServicesController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('jobsservices', JobsServicesController::class)->names('jobsservices');

    // Legacy admin routes removed - now using Filament resources
});
