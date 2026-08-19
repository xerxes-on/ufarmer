<?php

declare(strict_types=1);

namespace Modules\Analysis\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Analysis\Filament\Resources\AnalysisTypeResource;

class AnalysisPlugin implements Plugin
{
    public function getId(): string
    {
        return 'analysis';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AnalysisTypeResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
