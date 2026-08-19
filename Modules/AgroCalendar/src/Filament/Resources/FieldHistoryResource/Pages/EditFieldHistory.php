<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\FieldHistoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\FieldHistoryResource;

final class EditFieldHistory extends EditRecord
{
    protected static string $resource = FieldHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
