<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\FertilizerCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\FertilizerCategoryResource;

class CreateFertilizerCategory extends CreateRecord
{
    protected static string $resource = FertilizerCategoryResource::class;
}
