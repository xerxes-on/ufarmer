<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PestDetailResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\PlantScanner\Filament\Resources\PestDetailResource;

class EditPestDetail extends EditRecord
{
    protected static string $resource = PestDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
