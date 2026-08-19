<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource;

final class ListAgroInputPrices extends ListRecords
{
    protected static string $resource = AgroInputPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
