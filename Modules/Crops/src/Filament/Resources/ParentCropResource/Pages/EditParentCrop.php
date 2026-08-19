<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\ParentCropResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\ParentCropResource;

class EditParentCrop extends EditRecord
{
    protected static string $resource = ParentCropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
