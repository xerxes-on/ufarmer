<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AccuracyFactorItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorItemResource;

final class ListAccuracyFactorItems extends ListRecords
{
    protected static string $resource = AccuracyFactorItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
