<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\FertilizerCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\FertilizerCategoryResource;

class ListFertilizerCategories extends ListRecords
{
    protected static string $resource = FertilizerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
