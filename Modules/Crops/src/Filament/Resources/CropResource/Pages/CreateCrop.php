<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\CropResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\CropResource;

class CreateCrop extends CreateRecord
{
    protected static string $resource = CropResource::class;
}
