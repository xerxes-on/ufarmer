<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\MarketProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\MarketProductResource;

class ListMarketProducts extends ListRecords
{
    protected static string $resource = MarketProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
