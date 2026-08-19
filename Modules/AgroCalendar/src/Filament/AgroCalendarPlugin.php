<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class AgroCalendarPlugin implements Plugin
{
    public function getId(): string
    {
        return 'agrocalendar';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\AgroCalendar\Filament\Resources'
        );
    }

    public function boot(Panel $panel): void {}
}
