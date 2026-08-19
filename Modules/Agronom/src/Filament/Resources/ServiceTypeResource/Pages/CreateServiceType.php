<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\ServiceTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Agronom\Filament\Resources\ServiceTypeResource;

class CreateServiceType extends CreateRecord
{
    protected static string $resource = ServiceTypeResource::class;
}
