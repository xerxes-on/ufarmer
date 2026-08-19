<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource;

final class EditAccuracyFactor extends EditRecord
{
    protected static string $resource = AccuracyFactorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
