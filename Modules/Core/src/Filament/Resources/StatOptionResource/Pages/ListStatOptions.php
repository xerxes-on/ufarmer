<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\StatOptionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Core\Filament\Resources\StatOptionResource;

class ListStatOptions extends ListRecords
{
    protected static string $resource = StatOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
