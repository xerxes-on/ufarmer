<?php

declare(strict_types=1);

namespace Modules\Analysis\Providers;

use Illuminate\Support\ServiceProvider;

class AnalysisServiceProvider extends ServiceProvider
{
    protected string $name = 'Analysis';

    protected string $nameLower = 'analysis';

    public function boot(): void
    {
        $this->registerTranslations();
    }

    public function register(): void {}

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', $this->nameLower);
            $this->loadJsonTranslationsFrom(__DIR__.'/../../resources/lang');
        }
    }

    public function provides(): array
    {
        return [];
    }
}
