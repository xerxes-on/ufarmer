<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Translation\FileLoader;

class ExporterPlugin implements Plugin
{
    public function getId(): string
    {
        return 'exporter';
    }

    public function register(Panel $panel): void
    {
        $this->registerTranslations();

        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Exporter\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void {}

    private function registerTranslations(): void
    {
        $loader = app('translation.loader');

        if (! $loader instanceof FileLoader) {
            return;
        }

        $langPath = __DIR__.'/../lang';

        $loader->addNamespace('exporter', $langPath);
        $loader->addNamespace('Exporter', $langPath);
    }
}
