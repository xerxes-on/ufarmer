<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource;

final class ListAccuracyFactors extends ListRecords
{
    protected static string $resource = AccuracyFactorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
