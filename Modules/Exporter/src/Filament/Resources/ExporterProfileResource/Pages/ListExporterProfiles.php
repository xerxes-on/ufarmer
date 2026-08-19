<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources\ExporterProfileResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Exporter\Filament\Resources\ExporterProfileResource;

class ListExporterProfiles extends ListRecords
{
    protected static string $resource = ExporterProfileResource::class;
}
