<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PestDetailResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\PlantScanner\Filament\Resources\PestDetailResource;

class ListPestDetails extends ListRecords
{
    protected static string $resource = PestDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
