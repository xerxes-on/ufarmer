<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DrugCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\DrugCategoryResource;

class EditDrugCategory extends EditRecord
{
    protected static string $resource = DrugCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
