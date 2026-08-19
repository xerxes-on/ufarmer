<?php

declare(strict_types=1);

namespace Modules\Crops\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CropsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'crops';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'Modules\\Crops\\Filament\\Resources'
            );
    }

    public function boot(Panel $panel): void {}
}
