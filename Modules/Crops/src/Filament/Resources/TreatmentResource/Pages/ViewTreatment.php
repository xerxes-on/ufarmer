<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\TreatmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Crops\Filament\Resources\TreatmentResource;

class ViewTreatment extends ViewRecord
{
    protected static string $resource = TreatmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
