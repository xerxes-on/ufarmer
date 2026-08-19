<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\CalendarRunResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\AgroCalendar\Filament\Resources\CalendarRunResource;
use Modules\Predictions\Filament\Widgets\CalendarRunPredictionWidget;

final class ViewCalendarRun extends ViewRecord
{
    protected static string $resource = CalendarRunResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            CalendarRunPredictionWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
