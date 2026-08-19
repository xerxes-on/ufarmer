<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\FertilizerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\FertilizerResource;

class CreateFertilizer extends CreateRecord
{
    protected static string $resource = FertilizerResource::class;
}
