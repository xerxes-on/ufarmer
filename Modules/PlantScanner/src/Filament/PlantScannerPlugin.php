<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PlantScannerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'plant-scanner';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'Modules\\PlantScanner\\Filament\\Resources'
            );
    }

    public function boot(Panel $panel): void {}
}
