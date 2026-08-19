<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\ScoringThresholdResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalculator\Filament\Resources\ScoringThresholdResource;

class CreateScoringThreshold extends CreateRecord
{
    protected static string $resource = ScoringThresholdResource::class;
}
