<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CalendarRunResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\CalendarRunResource;

final class ListCalendarRuns extends ListRecords
{
    protected static string $resource = CalendarRunResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
