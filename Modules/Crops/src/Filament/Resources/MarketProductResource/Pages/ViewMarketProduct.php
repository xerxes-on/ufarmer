<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\MarketProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Crops\Filament\Resources\MarketProductResource;

class ViewMarketProduct extends ViewRecord
{
    protected static string $resource = MarketProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
