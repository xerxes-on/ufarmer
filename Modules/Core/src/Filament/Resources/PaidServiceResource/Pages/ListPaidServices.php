<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\PaidServiceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Core\Filament\Resources\PaidServiceResource;

class ListPaidServices extends ListRecords
{
    protected static string $resource = PaidServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
