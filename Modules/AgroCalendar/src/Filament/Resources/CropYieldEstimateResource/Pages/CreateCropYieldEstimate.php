<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CropYieldEstimateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\CropYieldEstimateResource;

final class CreateCropYieldEstimate extends CreateRecord
{
    protected static string $resource = CropYieldEstimateResource::class;
}
