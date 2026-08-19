<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\TreatmentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\TreatmentResource;

class CreateTreatment extends CreateRecord
{
    protected static string $resource = TreatmentResource::class;
}
