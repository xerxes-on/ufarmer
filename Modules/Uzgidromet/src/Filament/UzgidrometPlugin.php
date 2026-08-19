<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class UzgidrometPlugin implements Plugin
{
    public function getId(): string
    {
        return 'uzgidromet';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Uzgidromet\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void {}
}
