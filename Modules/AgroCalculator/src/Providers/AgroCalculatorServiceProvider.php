<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\AgroCalculator\Services\Factors\BandFactorCalculator;
use Modules\AgroCalculator\Services\Factors\BreakpointFactorCalculator;
use Modules\AgroCalculator\Services\Factors\ConstantFactorCalculator;
use Modules\AgroCalculator\Services\Factors\FactorCalculatorFactory;
use Modules\AgroCalculator\Services\Factors\PressureFactorCalculator;
use Modules\AgroCalculator\Services\Factors\RatioPiecewiseFactorCalculator;
use Modules\AgroCalculator\Services\Factors\SalinityFactorCalculator;
use Modules\AgroCalculator\Services\Factors\TemperatureTriangleFactorCalculator;
use Modules\AgroCalculator\Services\Factors\WaterBalanceFactorCalculator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * TODO(UFARM-2669): this whole module is unreachable. AgroCalculatorPlugin is
 * not listed in AdminPanelProvider and this provider is not in
 * bootstrap/providers.php, so the scoring engine, factor calculators, models,
 * and six Filament resources are all dead code. Its migrations and routes were
 * removed (ufarm-api owns migrations, and the agro_calculator_* tables it
 * created exist nowhere else), so the models now have no schema behind them.
 * Decide whether to wire the module up against ufarm-api's agro_calculator
 * tables or delete Modules/AgroCalculator outright.
 */
class AgroCalculatorServiceProvider extends ServiceProvider
{
    protected string $name = 'AgroCalculator';

    protected string $nameLower = 'agrocalculator';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerConfig();
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);

        $this->app->singleton(FactorCalculatorFactory::class, function () {
            return new FactorCalculatorFactory([
                new BandFactorCalculator,
                new RatioPiecewiseFactorCalculator,
                new SalinityFactorCalculator,
                new WaterBalanceFactorCalculator,
                new TemperatureTriangleFactorCalculator,
                new BreakpointFactorCalculator,
                new PressureFactorCalculator,
                new ConstantFactorCalculator,
            ]);
        });
    }

    protected function registerCommands(): void
    {
        // Intentionally left blank. Commands can be registered here once available.
    }

    protected function registerCommandSchedules(): void
    {
        // Intentionally left blank. Schedule bindings can be declared here when needed.
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $fallbackPath = __DIR__.'/../../resources/lang';
            if (! is_dir($fallbackPath)) {
                $fallbackPath = dirname(__DIR__, 2).'/lang';
            }

            $this->loadTranslationsFrom($fallbackPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($fallbackPath);
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = __DIR__.'/../../resources/views';
        if (! is_dir($sourcePath)) {
            $sourcePath = dirname(__DIR__, 2).'/resources/views';
        }

        if (! is_dir($sourcePath)) {
            return;
        }

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->nameLower.'-module-views']);

        $paths = [];
        foreach ((array) Config::get('view.paths', []) as $path) {
            $modulesPath = $path.'/modules/'.$this->nameLower;
            if (is_dir($modulesPath)) {
                $paths[] = $modulesPath;
            }
        }

        $this->loadViewsFrom(array_merge($paths, [$sourcePath]), $this->nameLower);
    }

    protected function registerConfig(): void
    {
        $configPath = __DIR__.'/../../config';

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKeyRaw = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $segments = explode('.', $this->nameLower.'.'.$configKeyRaw);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = $config === 'config.php' ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->mergeConfigFromRecursive($file->getPathname(), $key);
        }
    }

    protected function mergeConfigFromRecursive(string $path, string $key): void
    {
        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }

    public function provides(): array
    {
        return [];
    }
}
