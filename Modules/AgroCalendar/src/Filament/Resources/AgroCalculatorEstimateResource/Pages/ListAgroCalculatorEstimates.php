<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AgroCalculatorEstimateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\AgroCalculatorEstimateResource;

final class ListAgroCalculatorEstimates extends ListRecords
{
    protected static string $resource = AgroCalculatorEstimateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
