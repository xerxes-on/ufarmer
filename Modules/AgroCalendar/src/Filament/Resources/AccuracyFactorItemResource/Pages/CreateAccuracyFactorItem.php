<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AccuracyFactorItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorItemResource;

final class CreateAccuracyFactorItem extends CreateRecord
{
    protected static string $resource = AccuracyFactorItemResource::class;
}
