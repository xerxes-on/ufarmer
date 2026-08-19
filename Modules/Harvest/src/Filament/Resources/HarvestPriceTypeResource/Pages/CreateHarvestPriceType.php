<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestPriceTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Harvest\Filament\Resources\HarvestPriceTypeResource;

class CreateHarvestPriceType extends CreateRecord
{
    protected static string $resource = HarvestPriceTypeResource::class;
}
