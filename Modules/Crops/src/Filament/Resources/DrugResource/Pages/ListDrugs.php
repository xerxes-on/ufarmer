<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\DrugResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\DrugResource;

class ListDrugs extends ListRecords
{
    protected static string $resource = DrugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
