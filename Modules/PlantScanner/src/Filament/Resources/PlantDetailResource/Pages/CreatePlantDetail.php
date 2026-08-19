<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PlantDetailResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\PlantScanner\Filament\Resources\PlantDetailResource;

class CreatePlantDetail extends CreateRecord
{
    protected static string $resource = PlantDetailResource::class;
}
