<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource;

final class EditCropNutrientEffect extends EditRecord
{
    protected static string $resource = CropNutrientEffectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
