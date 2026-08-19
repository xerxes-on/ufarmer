<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PlantDetailResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\PlantScanner\Filament\Resources\PlantDetailResource;

class EditPlantDetail extends EditRecord
{
    protected static string $resource = PlantDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
