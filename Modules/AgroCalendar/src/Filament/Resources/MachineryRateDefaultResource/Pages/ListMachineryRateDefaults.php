<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource;

final class ListMachineryRateDefaults extends ListRecords
{
    protected static string $resource = MachineryRateDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
