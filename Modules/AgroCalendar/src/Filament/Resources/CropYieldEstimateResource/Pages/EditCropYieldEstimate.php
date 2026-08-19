<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CropYieldEstimateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\CropYieldEstimateResource;

final class EditCropYieldEstimate extends EditRecord
{
    protected static string $resource = CropYieldEstimateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
