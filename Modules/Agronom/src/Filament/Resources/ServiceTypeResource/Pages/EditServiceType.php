<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\ServiceTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Agronom\Filament\Resources\ServiceTypeResource;

class EditServiceType extends EditRecord
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
