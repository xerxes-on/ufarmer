<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\DiseaseResource;

class EditDisease extends EditRecord
{
    protected static string $resource = DiseaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
