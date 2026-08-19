<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\CropResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\CropResource;

class EditCrop extends EditRecord
{
    protected static string $resource = CropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
