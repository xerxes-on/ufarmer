<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class AgroPricesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'agro-prices';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'Modules\\AgroPrices\\Filament\\Resources'
            );
    }

    public function boot(Panel $panel): void {}
}
