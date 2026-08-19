<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PlantTagResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\PlantScanner\Filament\Resources\PlantTagResource;

class EditPlantTag extends EditRecord
{
    protected static string $resource = PlantTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
