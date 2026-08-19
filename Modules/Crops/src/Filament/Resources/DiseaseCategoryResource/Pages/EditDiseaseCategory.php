<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\DiseaseCategoryResource;

class EditDiseaseCategory extends EditRecord
{
    protected static string $resource = DiseaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
