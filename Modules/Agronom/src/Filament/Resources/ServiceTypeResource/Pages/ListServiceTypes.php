<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\ServiceTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Agronom\Filament\Resources\ServiceTypeResource;

class ListServiceTypes extends ListRecords
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
