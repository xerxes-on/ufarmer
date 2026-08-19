<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\CropResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\CropResource;

class ListCrops extends ListRecords
{
    protected static string $resource = CropResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
