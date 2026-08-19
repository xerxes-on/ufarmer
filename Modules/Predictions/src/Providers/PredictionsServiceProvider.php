<?php

declare(strict_types=1);

namespace Modules\Predictions\Providers;

use Illuminate\Support\ServiceProvider;

class PredictionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/config.php',
            'predictions'
        );
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'predictions');
    }
}
