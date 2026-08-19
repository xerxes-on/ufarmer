<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class HarvestPlugin implements Plugin
{
    public function getId(): string
    {
        return 'harvest';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Harvest\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void {}
}
