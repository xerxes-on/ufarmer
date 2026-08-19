<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class JobsServicesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'jobs-services';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__.'/Resources',
                for: 'Modules\\JobsServices\\Filament\\Resources'
            );
    }

    public function boot(Panel $panel): void {}
}
