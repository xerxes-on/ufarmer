<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\View;
use Modules\AgroCalculator\Filament\Pages\RunAgroCalculator;
use Modules\AgroCalculator\Filament\Resources\CalculatorRunResource;

final class AgroCalculatorPlugin implements Plugin
{
    public function getId(): string
    {
        return 'agro-calculator';
    }

    public function register(Panel $panel): void
    {
        View::addNamespace('agrocalculator', dirname(__DIR__, 2).'/resources/views');

        $panel->resources([
            CalculatorRunResource::class,
        ]);

        $panel->pages([
            RunAgroCalculator::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // No additional boot logic required.
    }
}
