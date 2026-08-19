<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DrugCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\DrugCategoryResource;

class ListDrugCategories extends ListRecords
{
    protected static string $resource = DrugCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
