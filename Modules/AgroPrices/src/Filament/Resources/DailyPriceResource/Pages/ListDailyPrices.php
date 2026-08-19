<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Filament\Resources\DailyPriceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroPrices\Filament\Resources\DailyPriceResource;

class ListDailyPrices extends ListRecords
{
    protected static string $resource = DailyPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
