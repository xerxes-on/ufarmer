<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\ScoringThresholdResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalculator\Filament\Resources\ScoringThresholdResource;

class ListScoringThresholds extends ListRecords
{
    protected static string $resource = ScoringThresholdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
