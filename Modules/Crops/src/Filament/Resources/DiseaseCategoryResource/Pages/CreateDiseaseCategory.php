<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\DiseaseCategoryResource;

class CreateDiseaseCategory extends CreateRecord
{
    protected static string $resource = DiseaseCategoryResource::class;
}
