<?php

declare(strict_types=1);

namespace Modules\Analysis\Filament\Resources\AnalysisTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Analysis\Filament\Resources\AnalysisTypeResource;

class ListAnalysisTypes extends ListRecords
{
    protected static string $resource = AnalysisTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
