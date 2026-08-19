<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\UnitResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\UnitResource;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
