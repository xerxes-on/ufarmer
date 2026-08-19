<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource;

final class EditLaborRateDefault extends EditRecord
{
    protected static string $resource = LaborRateDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
