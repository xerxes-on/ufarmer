<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Filament\Resources\DailyPriceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroPrices\Filament\Resources\DailyPriceResource;

class CreateDailyPrice extends CreateRecord
{
    protected static string $resource = DailyPriceResource::class;
}
