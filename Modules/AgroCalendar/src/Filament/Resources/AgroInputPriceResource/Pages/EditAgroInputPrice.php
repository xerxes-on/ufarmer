<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource;

final class EditAgroInputPrice extends EditRecord
{
    protected static string $resource = AgroInputPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
