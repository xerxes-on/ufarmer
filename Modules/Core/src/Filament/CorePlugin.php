<?php

declare(strict_types=1);

namespace Modules\Core\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Core\Filament\Pages\AdminNotificationChannels;

class CorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'core';
    }

    public function register(Panel $panel): void
    {
        app('view')->addNamespace('core', dirname(__DIR__).'/resources/views');

        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Core\\Filament\\Resources'
        )->pages([
            AdminNotificationChannels::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
