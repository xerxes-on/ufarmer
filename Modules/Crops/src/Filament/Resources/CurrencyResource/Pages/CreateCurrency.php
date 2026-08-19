<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\CurrencyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\CurrencyResource;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;
}
