<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\ParentCropResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\ParentCropResource;

class CreateParentCrop extends CreateRecord
{
    protected static string $resource = ParentCropResource::class;
}
