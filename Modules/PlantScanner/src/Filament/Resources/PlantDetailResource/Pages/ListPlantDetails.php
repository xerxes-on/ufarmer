<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PlantDetailResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\PlantScanner\Filament\Resources\PlantDetailResource;

class ListPlantDetails extends ListRecords
{
    protected static string $resource = PlantDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
