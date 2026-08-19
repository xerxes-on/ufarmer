<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\FertilizerCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\FertilizerCategoryResource;

class EditFertilizerCategory extends EditRecord
{
    protected static string $resource = FertilizerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
