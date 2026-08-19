<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestPriceTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Harvest\Filament\Resources\HarvestPriceTypeResource;

class EditHarvestPriceType extends EditRecord
{
    protected static string $resource = HarvestPriceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
