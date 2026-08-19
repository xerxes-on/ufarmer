<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource;

class ListUzgidrometFiles extends ListRecords
{
    protected static string $resource = UzgidrometFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
