<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DrugResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\DrugResource;

class CreateDrug extends CreateRecord
{
    protected static string $resource = DrugResource::class;
}
