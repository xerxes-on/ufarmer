<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\CityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Core\Filament\Resources\CityResource;

class CreateCity extends CreateRecord
{
    protected static string $resource = CityResource::class;
}
