<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\SpecializationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Agronom\Filament\Resources\SpecializationResource;

class ListSpecializations extends ListRecords
{
    protected static string $resource = SpecializationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
