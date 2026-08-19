<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\MarketProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\MarketProductResource;

class CreateMarketProduct extends CreateRecord
{
    protected static string $resource = MarketProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = 'manual';

        return $data;
    }
}
