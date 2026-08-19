<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\GeneralController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('generals', GeneralController::class)->names('general');
});
