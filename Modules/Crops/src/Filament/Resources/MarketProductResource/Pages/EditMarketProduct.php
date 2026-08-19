<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\MarketProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\MarketProductResource;

class EditMarketProduct extends EditRecord
{
    protected static string $resource = MarketProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
