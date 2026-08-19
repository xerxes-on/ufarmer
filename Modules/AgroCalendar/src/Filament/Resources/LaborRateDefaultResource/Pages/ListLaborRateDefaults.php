<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource;

final class ListLaborRateDefaults extends ListRecords
{
    protected static string $resource = LaborRateDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
