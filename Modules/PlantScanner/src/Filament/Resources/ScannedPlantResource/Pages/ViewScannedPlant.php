<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\ScannedPlantResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\PlantScanner\Filament\Resources\ScannedPlantResource;

class ViewScannedPlant extends ViewRecord
{
    protected static string $resource = ScannedPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
