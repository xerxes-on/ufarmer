<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\ServiceRequestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Agronom\Filament\Resources\ServiceRequestResource;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;
}
