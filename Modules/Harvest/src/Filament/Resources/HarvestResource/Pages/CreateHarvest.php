<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Harvest\Filament\Resources\HarvestResource;

class CreateHarvest extends CreateRecord
{
    protected static string $resource = HarvestResource::class;
}
