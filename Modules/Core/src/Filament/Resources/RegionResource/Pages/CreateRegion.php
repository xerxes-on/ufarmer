<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\RegionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Core\Filament\Resources\RegionResource;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;
}
