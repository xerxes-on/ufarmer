<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource;

final class EditMachineryRateDefault extends EditRecord
{
    protected static string $resource = MachineryRateDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
