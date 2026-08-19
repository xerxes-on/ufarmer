<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\DiseaseResource;

class ListDiseases extends ListRecords
{
    protected static string $resource = DiseaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
