<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\ServiceRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Agronom\Filament\Resources\ServiceRequestResource;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
