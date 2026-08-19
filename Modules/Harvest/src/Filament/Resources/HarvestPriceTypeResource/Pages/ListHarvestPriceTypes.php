<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestPriceTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Harvest\Filament\Resources\HarvestPriceTypeResource;

class ListHarvestPriceTypes extends ListRecords
{
    protected static string $resource = HarvestPriceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
