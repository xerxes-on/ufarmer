<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource;

final class ListCropNutrientEffects extends ListRecords
{
    protected static string $resource = CropNutrientEffectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
