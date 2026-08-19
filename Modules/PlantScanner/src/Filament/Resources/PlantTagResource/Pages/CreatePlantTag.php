<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PlantTagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\PlantScanner\Filament\Resources\PlantTagResource;

class CreatePlantTag extends CreateRecord
{
    protected static string $resource = PlantTagResource::class;
}
