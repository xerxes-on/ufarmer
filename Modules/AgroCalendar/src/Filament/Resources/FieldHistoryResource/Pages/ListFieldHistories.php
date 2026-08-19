<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\FieldHistoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalendar\Filament\Resources\FieldHistoryResource;

final class ListFieldHistories extends ListRecords
{
    protected static string $resource = FieldHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
