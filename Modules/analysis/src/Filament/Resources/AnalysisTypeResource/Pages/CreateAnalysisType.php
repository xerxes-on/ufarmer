<?php

declare(strict_types=1);

namespace Modules\Analysis\Filament\Resources\AnalysisTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Analysis\Filament\Resources\AnalysisTypeResource;

class CreateAnalysisType extends CreateRecord
{
    protected static string $resource = AnalysisTypeResource::class;
}
