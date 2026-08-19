<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource;

final class CreateAccuracyFactor extends CreateRecord
{
    protected static string $resource = AccuracyFactorResource::class;
}
