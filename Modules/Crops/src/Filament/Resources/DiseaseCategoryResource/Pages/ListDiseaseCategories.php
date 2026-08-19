<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DiseaseCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\DiseaseCategoryResource;

class ListDiseaseCategories extends ListRecords
{
    protected static string $resource = DiseaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
