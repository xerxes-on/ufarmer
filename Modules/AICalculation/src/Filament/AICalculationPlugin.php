<?php

declare(strict_types=1);

namespace Modules\AICalculation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\View;
use Illuminate\Translation\FileLoader;

final class AICalculationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'ai-calculation';
    }

    public function register(Panel $panel): void
    {
        $this->registerTranslations();

        $viewsPath = dirname(__DIR__, 2).'/resources/views';
        if (is_dir($viewsPath)) {
            View::addNamespace('ai-calculation', $viewsPath);
        }

        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\AICalculation\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void
    {
        // No additional boot logic required.
    }

    private function registerTranslations(): void
    {
        $loader = app('translation.loader');

        if (! $loader instanceof FileLoader) {
            return;
        }

        $langPath = dirname(__DIR__, 2).'/resources/lang';

        $loader->addNamespace('ai-calculation', $langPath);
        $loader->addNamespace('aicalculation', $langPath);
        $loader->addNamespace('AICalculation', $langPath);
    }
}
