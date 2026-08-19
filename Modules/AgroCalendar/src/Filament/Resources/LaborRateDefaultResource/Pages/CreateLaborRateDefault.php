<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource;

final class CreateLaborRateDefault extends CreateRecord
{
    protected static string $resource = LaborRateDefaultResource::class;
}
