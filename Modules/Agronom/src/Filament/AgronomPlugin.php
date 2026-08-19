<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\View;
use Modules\Agronom\Filament\Pages\AgronomSettings;

class AgronomPlugin implements Plugin
{
    public function getId(): string
    {
        return 'agronom';
    }

    public function register(Panel $panel): void
    {
        View::addNamespace('agronom', dirname(__DIR__, 2).'/resources/views');

        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'Modules\\Agronom\\Filament\\Resources'
            )
            ->pages([
                AgronomSettings::class,
            ]);
    }

    public function boot(Panel $panel): void {}
}
