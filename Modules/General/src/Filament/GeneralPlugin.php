<?php

declare(strict_types=1);

namespace Modules\General\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Translation\FileLoader;

class GeneralPlugin implements Plugin
{
    public function getId(): string
    {
        return 'general';
    }

    public function register(Panel $panel): void
    {
        $this->registerTranslations();

        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'Modules\\General\\Filament\\Resources'
            );
    }

    public function boot(Panel $panel): void {}

    private function registerTranslations(): void
    {
        $loader = app('translation.loader');

        if (! $loader instanceof FileLoader) {
            return;
        }

        $langPath = dirname(__DIR__, 2).'/resources/lang';

        $loader->addNamespace('general', $langPath);
        $loader->addNamespace('General', $langPath);
    }
}
