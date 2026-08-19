<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource;

final class CreateMachineryRateDefault extends CreateRecord
{
    protected static string $resource = MachineryRateDefaultResource::class;
}
