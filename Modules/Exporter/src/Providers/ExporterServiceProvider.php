<?php

declare(strict_types=1);

namespace Modules\Exporter\Providers;

use Illuminate\Support\ServiceProvider;

class ExporterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'exporter');
    }
}
