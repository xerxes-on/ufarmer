<?php

declare(strict_types=1);

namespace Modules\Predictions\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Translation\FileLoader;

/**
 * Registers the module's views, translations, and config. It no longer
 * contributes resources: PredictionFactorResource and HarvestRecordResource
 * were second Filament screens over AccuracyFactor and Harvest, models already
 * edited under "Agro Calculator" and "Marketplace & Prices" respectively, so
 * the same row could be changed from two places (UFARM-2669). The module still
 * ships CalendarRunPredictionWidget, which ViewCalendarRun renders.
 */
final class PredictionsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'predictions';
    }

    public function register(Panel $panel): void
    {
        $this->registerModuleFiles();
    }

    public function boot(Panel $panel): void {}

    private function registerModuleFiles(): void
    {
        $modulePath = dirname(__DIR__, 2);

        View::addNamespace('predictions', "{$modulePath}/resources/views");

        $loader = app('translation.loader');

        if ($loader instanceof FileLoader) {
            $loader->addNamespace('predictions', "{$modulePath}/resources/lang");
        }

        Config::set('predictions', array_replace_recursive(
            require "{$modulePath}/config/config.php",
            config('predictions', [])
        ));
    }
}
