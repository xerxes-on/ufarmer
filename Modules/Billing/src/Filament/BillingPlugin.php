<?php

declare(strict_types=1);

namespace Modules\Billing\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class BillingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'billing';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Billing\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void {}
}
