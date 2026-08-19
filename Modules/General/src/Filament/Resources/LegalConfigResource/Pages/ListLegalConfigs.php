<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\LegalConfigResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\General\Filament\Resources\LegalConfigResource;

class ListLegalConfigs extends ListRecords
{
    protected static string $resource = LegalConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
