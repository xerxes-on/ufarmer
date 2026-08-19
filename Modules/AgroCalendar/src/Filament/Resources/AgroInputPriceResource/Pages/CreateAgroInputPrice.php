<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource;

final class CreateAgroInputPrice extends CreateRecord
{
    protected static string $resource = AgroInputPriceResource::class;
}
