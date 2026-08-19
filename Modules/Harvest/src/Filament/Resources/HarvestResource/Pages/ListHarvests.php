<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Harvest\Filament\Resources\HarvestResource;

class ListHarvests extends ListRecords
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
