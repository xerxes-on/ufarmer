<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\FertilizerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\FertilizerResource;

class ListFertilizers extends ListRecords
{
    protected static string $resource = FertilizerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
