<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\FieldHistoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalendar\Filament\Resources\FieldHistoryResource;

final class CreateFieldHistory extends CreateRecord
{
    protected static string $resource = FieldHistoryResource::class;
}
