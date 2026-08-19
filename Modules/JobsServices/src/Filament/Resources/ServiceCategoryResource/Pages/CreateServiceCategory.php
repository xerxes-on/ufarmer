<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\ServiceCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\JobsServices\Filament\Resources\ServiceCategoryResource;

class CreateServiceCategory extends CreateRecord
{
    protected static string $resource = ServiceCategoryResource::class;
}
