<?php

declare(strict_types=1);

namespace Xerxes\BillingClient;

use Illuminate\Support\ServiceProvider;
use Makhweb\BillingClient\Contracts\PaymentWebhookContract;
use Xerxes\BillingClient\Contracts\EntityParserInterface;
use Xerxes\BillingClient\Services\EntityIdParser;
use Xerxes\BillingClient\Services\PaymentWebhookHandler;

class UfarmBillingClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ufarm-billing-client.php',
            'ufarm-billing-client'
        );

        $this->app->singleton(
            EntityParserInterface::class,
            EntityIdParser::class
        );

        $this->app->singleton(
            PaymentWebhookContract::class,
            PaymentWebhookHandler::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ufarm-billing-client.php' => config_path('ufarm-billing-client.php'),
            ], 'ufarm-billing-client-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'ufarm-billing-client-migrations');
        }
    }
}
