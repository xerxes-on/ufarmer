<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\CropParameterSetResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalculator\Filament\Resources\CropParameterSetResource;

class ListCropParameterSets extends ListRecords
{
    protected static string $resource = CropParameterSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
