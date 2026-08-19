<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\ParentCropResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\ParentCropResource;

class ListParentCrops extends ListRecords
{
    protected static string $resource = ParentCropResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
