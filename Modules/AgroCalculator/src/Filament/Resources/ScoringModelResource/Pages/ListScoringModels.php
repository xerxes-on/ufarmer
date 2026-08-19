<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\ScoringModelResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalculator\Filament\Resources\ScoringModelResource;

class ListScoringModels extends ListRecords
{
    protected static string $resource = ScoringModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
