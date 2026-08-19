<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DrugCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\DrugCategoryResource;

class CreateDrugCategory extends CreateRecord
{
    protected static string $resource = DrugCategoryResource::class;
}
