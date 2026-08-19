<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\JobsServices\Http\Controllers\Api\CategoryController;
use Modules\JobsServices\Http\Controllers\Api\JobApplicationController;
use Modules\JobsServices\Http\Controllers\Api\JobController;
use Modules\JobsServices\Http\Controllers\Api\ServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function (): void {
    // Public routes
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);

    // Public search routes
    Route::get('jobs/search', [JobController::class, 'search']);
    Route::get('services/search', [ServiceController::class, 'search']);

    // Public view routes
    Route::get('jobs/{job}', [JobController::class, 'show']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function (): void {
        // Job routes
        Route::prefix('jobs')->group(function (): void {
            Route::get('/', [JobController::class, 'index']);
            Route::post('/', [JobController::class, 'store']);
            Route::put('{job}', [JobController::class, 'update']);
            Route::delete('{job}', [JobController::class, 'destroy']);
            Route::patch('{job}/status', [JobController::class, 'updateStatus']);

            // Job application routes
            Route::post('{job}/apply', [JobApplicationController::class, 'apply']);
            Route::get('{job}/applications', [JobApplicationController::class, 'jobApplications']);
            Route::patch('{job}/applications/{jobApplication}/respond', [JobApplicationController::class, 'respond']);
        });

        // Service routes
        Route::prefix('services')->group(function (): void {
            Route::get('/', [ServiceController::class, 'index']);
            Route::post('/', [ServiceController::class, 'store']);
            Route::put('{service}', [ServiceController::class, 'update']);
            Route::delete('{service}', [ServiceController::class, 'destroy']);
            Route::patch('{service}/status', [ServiceController::class, 'updateStatus']);
        });

        // Job application routes
        Route::prefix('job-applications')->group(function (): void {
            Route::get('my-applications', [JobApplicationController::class, 'myApplications']);
            Route::patch('{jobApplication}/cancel', [JobApplicationController::class, 'cancel']);
        });
    });
});
