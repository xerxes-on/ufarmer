<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\ScoringRunResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalculator\Filament\Resources\ScoringRunResource;

class ListScoringRuns extends ListRecords
{
    protected static string $resource = ScoringRunResource::class;
}
