<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource;

final class CreateCropNutrientEffect extends CreateRecord
{
    protected static string $resource = CropNutrientEffectResource::class;
}
