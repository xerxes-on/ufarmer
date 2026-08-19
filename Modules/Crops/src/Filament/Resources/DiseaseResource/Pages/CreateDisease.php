<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\DiseaseResource;

class CreateDisease extends CreateRecord
{
    protected static string $resource = DiseaseResource::class;
}
