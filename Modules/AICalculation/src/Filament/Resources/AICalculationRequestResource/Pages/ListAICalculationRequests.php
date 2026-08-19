<?php

declare(strict_types=1);

namespace Modules\AICalculation\Filament\Resources\AICalculationRequestResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\AICalculation\Filament\Resources\AICalculationRequestResource;

class ListAICalculationRequests extends ListRecords
{
    protected static string $resource = AICalculationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
