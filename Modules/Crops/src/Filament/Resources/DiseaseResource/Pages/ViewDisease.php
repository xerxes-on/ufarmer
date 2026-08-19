<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Crops\Filament\Resources\DiseaseResource;

class ViewDisease extends ViewRecord
{
    protected static string $resource = DiseaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
