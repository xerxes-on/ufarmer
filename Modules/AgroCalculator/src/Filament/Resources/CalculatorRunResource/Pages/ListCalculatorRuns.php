<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\CalculatorRunResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalculator\Filament\Resources\CalculatorRunResource;

class ListCalculatorRuns extends ListRecords
{
    protected static string $resource = CalculatorRunResource::class;
}
