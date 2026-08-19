<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PlantTagResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\PlantScanner\Filament\Resources\PlantTagResource;

class ListPlantTags extends ListRecords
{
    protected static string $resource = PlantTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
