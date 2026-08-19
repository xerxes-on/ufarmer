<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Harvest\Filament\Resources\HarvestResource;

class ViewHarvest extends ViewRecord
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
