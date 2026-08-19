<?php

declare(strict_types=1);

namespace Makhweb\BillingClient;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Makhweb\BillingClient\Http\Controllers\WebhookController;

class BillingClientServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/billing-client.php', 'billing-client');

        $prefix = config('billing-client.webhook_prefix');

        if ($prefix && ! $this->app->routesAreCached()) {
            Route::prefix($prefix)->group(function (): void {
                Route::post('/currency-rate', [WebhookController::class, 'currencyRate']);
                Route::post('/authorize', [WebhookController::class, 'authorize']);
                Route::post('/transaction/callback', [WebhookController::class, 'transactionHandle']);
                Route::post('/transaction/revert-allowed', [WebhookController::class, 'transactionRevertAllowed']);
            });
        }
    }
}
