<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\PestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\PestResource;

class ListPests extends ListRecords
{
    protected static string $resource = PestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
