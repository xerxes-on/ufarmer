<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Filament\Resources\DailyPriceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AgroPrices\Filament\Resources\DailyPriceResource;

class EditDailyPrice extends EditRecord
{
    protected static string $resource = DailyPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
